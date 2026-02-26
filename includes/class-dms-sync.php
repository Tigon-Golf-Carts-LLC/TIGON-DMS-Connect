<?php
/**
 * DMS Inventory Sync Handler
 *
 * Background sync system for DMS inventory to WooCommerce products
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class DMS_Sync
{
    /**
     * Image base URL for DMS images
     * Now uses centralized URL from DMS_API class
     *
     * @var string
     */
    private static function get_image_base_url()
    {
        return DMS_API::get_s3_carts_url();
    }

    /**
     * Sync all inventory from DMS API
     *
     * Fetches all carts using pagination and creates/updates WooCommerce products
     *
     * @return array Sync results with stats
     */
    public static function sync_inventory()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return array(
                'success' => false,
                'message' => 'WooCommerce is not active',
            );
        }

        $stats = array(
            'created'   => 0,
            'updated'   => 0,
            'skipped'   => 0,
            'errors'    => 0,
            'sold'      => 0,
            'total'     => 0,
        );

        // Collect all active DMS cart IDs so we can detect sold products afterwards
        $active_cart_ids = array();

        $page_number = 0;
        $page_size = 20;

        // Paginate through all carts
        // The /get-carts API returns a raw JSON array: [{...}, {...}, {...}]
        // Pagination stops when an empty array is returned
        while (true) {
            // Fetch carts for this page
            $carts = DMS_API::get_carts($page_number, $page_size);

            // Check for API error
            if ($carts === false) {
                $stats['errors']++;
                break; // Stop on error
            }

            // Guard: ensure we have an array
            if (!is_array($carts)) {
                $stats['errors']++;
                break;
            }

            // Stop pagination when API returns an empty array
            if (empty($carts)) {
                break;
            }

            // Process each cart
            foreach ($carts as $cart_data) {
                $stats['total']++;

                // Skip if cart doesn't have _id
                if (empty($cart_data['_id'])) {
                    $stats['skipped']++;
                    continue;
                }

                $cart_id = $cart_data['_id'];
                $active_cart_ids[] = $cart_id;

                // Stage cart data in local tigon_dms_carts table
                // This preserves ALL DMS fields locally for debugging/querying
                $store_id   = $cart_data['cartLocation']['locationId'] ?? '';
                $store_name = DMS_API::get_city_by_store_id($store_id);
                \Tigon\DmsConnect\Admin\CartModel::upsert_from_api($cart_data, $store_name, '', $store_id);

                // Check if product already exists (for stats tracking)
                $existing_product_id = tigon_dms_get_product_by_cart_id($cart_id);
                $was_existing = (bool) $existing_product_id;

                try {
                    // Use existing function to create/update product (idempotent)
                    $product_id = tigon_dms_ensure_woo_product($cart_data, $cart_id);

                    if ($product_id) {
                        // Handle images (featured + gallery)
                        self::sync_product_images($product_id, $cart_data);

                        // Track stats
                        if ($was_existing) {
                            $stats['updated']++;
                        } else {
                            $stats['created']++;
                        }
                    } else {
                        $stats['errors']++;
                    }
                } catch (Exception $e) {
                    $stats['errors']++;
                    error_log('DMS Sync Error for cart ' . $cart_id . ': ' . $e->getMessage());
                }
            }

            // Move to next page
            $page_number++;

            // Prevent infinite loops (safety limit)
            if ($page_number > 1000) {
                break;
            }
        }

        // -----------------------------------------------------------------
        // Sold product detection (mirrors Database_Write_Controller cleanup)
        //
        // Find WooCommerce products with _dms_cart_id that are no longer
        // in the active DMS inventory and mark them as sold/out-of-stock.
        // -----------------------------------------------------------------
        if (!empty($active_cart_ids)) {
            $sold_products = self::detect_sold_products($active_cart_ids);
            foreach ($sold_products as $sold_product_id) {
                if (function_exists('tigon_dms_handle_sold_product')) {
                    $handled = tigon_dms_handle_sold_product($sold_product_id);
                    if ($handled) {
                        $stats['sold']++;
                    }
                }
            }
        }

        return array(
            'success' => true,
            'stats'   => $stats,
        );
    }


    /**
     * Detect WooCommerce products whose DMS cart IDs are no longer active.
     *
     * Queries all published products with a _dms_cart_id meta and returns
     * the product IDs whose cart ID is NOT in the active set.
     *
     * @param array $active_cart_ids Array of DMS cart IDs still in inventory
     * @return array Product IDs that are no longer in DMS
     */
    private static function detect_sold_products($active_cart_ids)
    {
        global $wpdb;

        // Get all published products that have a DMS cart ID
        $results = $wpdb->get_results(
            "SELECT p.ID, pm.meta_value AS cart_id
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_dms_cart_id'
             WHERE p.post_type = 'product' AND p.post_status = 'publish'",
            ARRAY_A
        );

        if (empty($results)) {
            return array();
        }

        $sold_ids = array();
        $active_set = array_flip($active_cart_ids); // O(1) lookups

        foreach ($results as $row) {
            if (!isset($active_set[$row['cart_id']])) {
                $sold_ids[] = (int) $row['ID'];
            }
        }

        return $sold_ids;
    }

    /**
     * Sync product images from DMS cart data
     *
     * Sets first image as featured image, rest as gallery images
     * Avoids duplicate uploads by checking existing attachment URLs
     *
     * @param int   $product_id WooCommerce product ID
     * @param array $cart_data  Full DMS cart payload
     * @return void
     */
    /**
     * Public wrapper for sync_product_images (used by selective sync AJAX handler).
     */
    public static function sync_product_images_public($product_id, $cart_data)
    {
        self::sync_product_images($product_id, $cart_data);
    }

    private static function sync_product_images($product_id, $cart_data)
    {
        // Use centralized image resolver (handles coming-soon placeholder)
        $resolved_urls = DMS_API::resolve_cart_image_urls($cart_data);

        if (empty($resolved_urls)) {
            return;
        }

        $attachment_ids = array();
        $featured_image_id = null;

        foreach ($resolved_urls as $index => $image_url) {
            if (empty($image_url)) {
                continue;
            }

            // Check if attachment already exists by URL
            $existing_attachment_id = self::get_attachment_id_by_url($image_url);

            if ($existing_attachment_id) {
                // Use existing attachment
                $attachment_id = $existing_attachment_id;
            } else {
                // Upload new image
                $attachment_id = self::upload_image_from_url($image_url, $product_id);

                if (!$attachment_id) {
                    continue; // Skip on upload failure
                }
            }

            if ($attachment_id) {
                $attachment_ids[] = $attachment_id;

                // First image is featured image
                if ($index === 0) {
                    $featured_image_id = $attachment_id;
                }
            }
        }

        // Set featured image
        if ($featured_image_id) {
            set_post_thumbnail($product_id, $featured_image_id);
        }

        // Set gallery images (all images except featured)
        $gallery_ids = array_slice($attachment_ids, 1); // Skip first image
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        } else {
            // No gallery images, clear the meta
            update_post_meta($product_id, '_product_image_gallery', '');
        }
    }

    /**
     * Get attachment ID by URL (check if image already exists)
     *
     * @param string $image_url Full image URL
     * @return int|false Attachment ID or false if not found
     */
    private static function get_attachment_id_by_url($image_url)
    {
        global $wpdb;

        // Extract filename from URL
        $filename = basename(parse_url($image_url, PHP_URL_PATH));

        if (empty($filename)) {
            return false;
        }

        // Search for attachment by filename in postmeta
        $attachment_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_wp_attached_file'
                 AND meta_value LIKE %s
                 LIMIT 1",
                '%' . $wpdb->esc_like($filename) . '%'
            )
        );

        if ($attachment_id) {
            return (int) $attachment_id;
        }

        // Also check by URL in postmeta (for externally hosted images)
        $attachment_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_dms_image_url'
                 AND meta_value = %s
                 LIMIT 1",
                $image_url
            )
        );

        return $attachment_id ? (int) $attachment_id : false;
    }

    /**
     * Upload image from URL and attach to product
     *
     * @param string $image_url   Full image URL
     * @param int    $product_id  WooCommerce product ID
     * @return int|false Attachment ID or false on failure
     */
    private static function upload_image_from_url($image_url, $product_id)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Download image to temp file
        $temp_file = download_url($image_url);

        if (is_wp_error($temp_file)) {
            return false;
        }

        // Prepare file array for wp_handle_sideload
        $file_array = array(
            'name'     => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $temp_file,
        );

        // Handle sideload
        $file = wp_handle_sideload($file_array, array('test_form' => false));

        if (isset($file['error'])) {
            @unlink($temp_file);
            return false;
        }

        // Create attachment
        $attachment_data = array(
            'post_mime_type' => $file['type'],
            'post_title'     => sanitize_file_name(pathinfo($file_array['name'], PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment($attachment_data, $file['file'], $product_id);

        if (is_wp_error($attachment_id)) {
            @unlink($file['file']);
            return false;
        }

        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attachment_id, $file['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        // Store original URL in meta to avoid duplicates
        update_post_meta($attachment_id, '_dms_image_url', $image_url);

        return $attachment_id;
    }

    /**
     * Get sync interval option (in hours)
     *
     * @return int Hours between syncs
     */
    public static function get_sync_interval()
    {
        return get_option('dms_sync_interval', 6); // Default: 6 hours
    }

    /**
     * Set sync interval option (in hours)
     *
     * @param int $hours Hours between syncs
     * @return void
     */
    public static function set_sync_interval($hours)
    {
        update_option('dms_sync_interval', max(1, (int) $hours));
    }
}

