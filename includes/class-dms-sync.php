<?php
/**
 * DMS Inventory Sync Handler
 *
 * Background sync system for DMS inventory to WooCommerce products.
 *
 * Performance safeguards:
 *  - Max execution time enforcement (default 120 s, well under PHP limits)
 *  - Memory ceiling check before every page (stops at 80 % of WP memory limit)
 *  - 0.5 s sleep between DMS API pages to avoid hammering the API & Apache
 *  - Batch image-attachment lookup (1 query per page instead of 2 per image)
 *  - Page cap lowered from 1 000 to 100 (2 000 carts max per run)
 *  - download_url() called with a 15 s timeout to prevent hung connections
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class DMS_Sync
{
    /**
     * Hard ceiling for pages processed in a single sync run.
     * 100 pages × 20 carts = 2 000 carts max.  Adjust as needed.
     */
    private const MAX_PAGES = 100;

    /**
     * Seconds to allow for the entire sync run.
     * set_time_limit() is called at the start; the loop also checks wall-clock
     * time so it gracefully stops even when set_time_limit is a no-op.
     */
    private const MAX_EXECUTION_SECONDS = 120;

    /**
     * Fraction of the WP memory limit at which sync should stop.
     * 0.80 = stop when 80 % of memory is consumed.
     */
    private const MEMORY_CEILING_RATIO = 0.80;

    /**
     * Seconds to sleep between API pages.
     * Gives Apache / MySQL breathing room during large syncs.
     */
    private const PAGE_SLEEP_SECONDS = 0.5;

    /**
     * Timeout (seconds) passed to download_url() for each image sideload.
     */
    private const IMAGE_DOWNLOAD_TIMEOUT = 15;

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
     * Parse WP_MEMORY_LIMIT into bytes.
     */
    private static function get_memory_limit_bytes(): int
    {
        $limit = defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : '256M';
        $unit = strtoupper(substr($limit, -1));
        $value = (int) $limit;
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return $value;
        }
    }

    /**
     * Returns true when the process is close to its memory ceiling.
     */
    private static function memory_exceeded(): bool
    {
        return memory_get_usage(true) >= self::get_memory_limit_bytes() * self::MEMORY_CEILING_RATIO;
    }

    /**
     * Sync all inventory from DMS API
     *
     * Fetches carts using pagination, creates/updates WooCommerce products,
     * and respects time + memory budgets so Apache stays healthy.
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

        // Try to extend execution time (no-op on many hosts, but helps when allowed)
        @set_time_limit(self::MAX_EXECUTION_SECONDS + 30);
        $start_time = time();

        $stats = array(
            'created'          => 0,
            'updated'          => 0,
            'skipped'          => 0,
            'errors'           => 0,
            'total'            => 0,
            'stopped_reason'   => null,
        );

        $page_number = 0;
        $page_size = 20;

        // Paginate through carts
        while (true) {
            // --- Budget checks BEFORE fetching next page ---
            if ((time() - $start_time) >= self::MAX_EXECUTION_SECONDS) {
                $stats['stopped_reason'] = 'time_limit';
                break;
            }
            if (self::memory_exceeded()) {
                $stats['stopped_reason'] = 'memory_limit';
                break;
            }
            if ($page_number >= self::MAX_PAGES) {
                $stats['stopped_reason'] = 'page_cap';
                break;
            }

            // Fetch carts for this page
            $carts = DMS_API::get_carts($page_number, $page_size);

            // Check for API error
            if ($carts === false) {
                $stats['errors']++;
                $stats['stopped_reason'] = 'api_error';
                break;
            }

            // Guard: ensure we have an array
            if (!is_array($carts)) {
                $stats['errors']++;
                $stats['stopped_reason'] = 'api_invalid_response';
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
                } catch (\Exception $e) {
                    $stats['errors']++;
                    error_log('DMS Sync Error for cart ' . $cart_id . ': ' . $e->getMessage());
                }
            }

            // Move to next page
            $page_number++;

            // Breathe — let Apache serve other requests
            if (self::PAGE_SLEEP_SECONDS > 0) {
                usleep((int) (self::PAGE_SLEEP_SECONDS * 1_000_000));
            }
        }

        // Log sync summary for debugging
        error_log(sprintf(
            'DMS Sync complete: %d total, %d created, %d updated, %d skipped, %d errors, pages=%d, stopped=%s',
            $stats['total'],
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['errors'],
            $page_number,
            $stats['stopped_reason'] ?? 'finished'
        ));

        return array(
            'success' => true,
            'stats'   => $stats,
        );
    }


    /**
     * Sync product images from DMS cart data
     *
     * Sets first image as featured image, rest as gallery images.
     * Uses batch lookup to find existing attachments in one query.
     *
     * @param int   $product_id WooCommerce product ID
     * @param array $cart_data  Full DMS cart payload
     * @return void
     */
    private static function sync_product_images($product_id, $cart_data)
    {
        $image_urls = $cart_data['imageUrls'] ?? array();

        if (empty($image_urls) || !is_array($image_urls)) {
            return;
        }

        // Build full URLs and extract filenames upfront
        $base_url = self::get_image_base_url();
        $full_urls = [];
        $filenames = [];
        foreach ($image_urls as $image_filename) {
            if (empty($image_filename)) {
                continue;
            }
            $url = $base_url . ltrim($image_filename, '/');
            $full_urls[] = $url;
            $filenames[] = basename(parse_url($url, PHP_URL_PATH));
        }

        if (empty($full_urls)) {
            return;
        }

        // --- Batch lookup: find all existing attachments in 2 queries total ---
        $existing_map = self::batch_get_attachment_ids($full_urls, $filenames);

        $attachment_ids = array();
        $featured_image_id = null;

        foreach ($full_urls as $index => $image_url) {
            $attachment_id = $existing_map[$image_url] ?? null;

            if (!$attachment_id) {
                // Upload new image (with timeout to prevent hung connections)
                $attachment_id = self::upload_image_from_url($image_url, $product_id);

                if (!$attachment_id) {
                    continue;
                }
            }

            $attachment_ids[] = $attachment_id;

            if ($index === 0) {
                $featured_image_id = $attachment_id;
            }
        }

        // Set featured image
        if ($featured_image_id) {
            set_post_thumbnail($product_id, $featured_image_id);
        }

        // Set gallery images (all images except featured)
        $gallery_ids = array_slice($attachment_ids, 1);
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        } else {
            update_post_meta($product_id, '_product_image_gallery', '');
        }
    }

    /**
     * Batch-lookup attachment IDs for multiple image URLs.
     *
     * Replaces the old per-image get_attachment_id_by_url() which ran
     * 2 queries per image.  This runs exactly 2 queries total regardless
     * of how many images are in the batch.
     *
     * @param string[] $full_urls  Full image URLs
     * @param string[] $filenames  Corresponding basenames
     * @return array<string,int>   Map of URL → attachment_id
     */
    private static function batch_get_attachment_ids(array $full_urls, array $filenames): array
    {
        global $wpdb;
        $map = [];

        if (empty($full_urls)) {
            return $map;
        }

        // Query 1: match by _dms_image_url (exact match, fast)
        $url_placeholders = implode(',', array_fill(0, count($full_urls), '%s'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, meta_value FROM {$wpdb->postmeta}
                 WHERE meta_key = '_dms_image_url'
                 AND meta_value IN ({$url_placeholders})",
                $full_urls
            )
        );
        $found_urls = [];
        if ($rows) {
            foreach ($rows as $row) {
                $map[$row->meta_value] = (int) $row->post_id;
                $found_urls[$row->meta_value] = true;
            }
        }

        // Query 2: for URLs not found above, try _wp_attached_file by filename
        $missing_indices = [];
        foreach ($full_urls as $i => $url) {
            if (!isset($found_urls[$url])) {
                $missing_indices[] = $i;
            }
        }
        if (!empty($missing_indices)) {
            // Build OR conditions for filename LIKE matches
            $like_clauses = [];
            $like_params = [];
            foreach ($missing_indices as $i) {
                $like_clauses[] = "meta_value LIKE %s";
                $like_params[] = '%' . $wpdb->esc_like($filenames[$i]) . '%';
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT post_id, meta_value FROM {$wpdb->postmeta}
                     WHERE meta_key = '_wp_attached_file'
                     AND (" . implode(' OR ', $like_clauses) . ")",
                    $like_params
                )
            );
            if ($rows) {
                // Match rows back to URLs by filename
                $filename_to_url = [];
                foreach ($missing_indices as $i) {
                    $filename_to_url[$filenames[$i]] = $full_urls[$i];
                }
                foreach ($rows as $row) {
                    $attached_basename = basename($row->meta_value);
                    if (isset($filename_to_url[$attached_basename])) {
                        $url = $filename_to_url[$attached_basename];
                        if (!isset($map[$url])) {
                            $map[$url] = (int) $row->post_id;
                        }
                    }
                }
            }
        }

        return $map;
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

        // Download with explicit timeout so a hung S3 connection cannot stall the whole sync
        $temp_file = download_url($image_url, self::IMAGE_DOWNLOAD_TIMEOUT);

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
