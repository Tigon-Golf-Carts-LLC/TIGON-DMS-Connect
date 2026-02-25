<?php

namespace Tigon\DmsConnect\Admin;

use Tigon\DmsConnect\Includes\Product_Fields;
use Tigon\DmsConnect\Admin\Database_Write_Controller;

abstract class Ajax_Import_Controller extends \Tigon\DmsConnect\Abstracts\Abstract_Import_Controller
{
    private function __construct()
    {
    }

    /**
     * Allowed Product_Fields constant names for the forced fields feature.
     */
    private static $allowed_field_constants = [
        'NAME', 'SHORT_DESCRIPTION', 'DESCRIPTION', 'PUBLISHED', 'COMMENT_STATUS',
        'PING_STATUS', 'MENU_ORDER', 'POST_TYPE', 'COMMENT_COUNT', 'POST_AUTHOR',
        'SLUG', 'SKU', 'TAX_STATUS', 'TAX_CLASS', 'MANAGE_STOCK', 'BACKORDERS_ALLOWED',
        'SOLD_INDIVIDUALLY', 'IS_VIRTUAL', 'DOWNLOADABLE', 'DOWNLOAD_LIMIT',
        'DOWNLOAD_EXPIRY', 'STOCK', 'IN_STOCK', 'GUI', 'IMAGES', 'PRICE', 'SALE_PRICE',
        'YOAST_SEO_TITLE', 'META_DESCRIPTION', 'PRIMARY_CATEGORY', 'PRIMARY_LOCATION',
        'PRIMARY_MODEL', 'PRIMARY_ADDED_FEATURE', 'BIT_IS_CORNERSTONE', 'CUSTOM_TABS',
        'ATTR_EXCLUDE_GLOBAL_FORMS', 'CUSTOM_PRODUCT_OPTIONS', 'CONDITION', 'GOOGLE_BRAND',
        'GOOGLE_COLOR', 'GOOGLE_PATTERN', 'GENDER', 'GOOGLE_SIZE_SYSTEM', 'ADULT_CONTENT',
        'GOOGLE_CATEGORY', 'AGE_GROUP', 'PRODUCT_IMAGE_SOURCE', 'FACEBOOK_SYNC',
        'FACEBOOK_VISIBILITY', 'MONRONEY_STICKER', 'MONRONEY_CONTAINER_ID', 'TIGONWM_TEXT',
        'TAXONOMY_TERMS', 'ALL_FIELDS',
    ];

    /**
     * Verify the current AJAX request has a valid nonce and admin capability.
     * Terminates with 403 on failure.
     */
    private static function verify_ajax_request()
    {
        check_ajax_referer('tigon_dms_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }
    }

    /**
     * Reconstruct a Database_Object from a JSON-encoded data array.
     * Safe replacement for unserialize().
     *
     * @param string $json_data JSON string of the Database_Object data array
     * @return Database_Object|null
     */
    private static function decode_database_object(string $json_data)
    {
        $raw = json_decode($json_data, true);
        if (!is_array($raw)) {
            return null;
        }
        $db_obj = new Database_Object();
        // Only restore known top-level keys
        if (isset($raw['_id'])) $db_obj->data['_id'] = $raw['_id'];
        if (isset($raw['method'])) $db_obj->data['method'] = sanitize_text_field($raw['method']);
        if (isset($raw['posts']) && is_array($raw['posts'])) $db_obj->data['posts'] = $raw['posts'];
        if (isset($raw['postmeta']) && is_array($raw['postmeta'])) $db_obj->data['postmeta'] = $raw['postmeta'];
        if (isset($raw['term_relationships']) && is_array($raw['term_relationships'])) $db_obj->data['term_relationships'] = $raw['term_relationships'];
        return $db_obj;
    }

    /**
     * Ajax wrapper for get_db
     * @return never
     */
    public static function query_dms()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request
        // AJAX produces unwanted slashes
        $endpoint = sanitize_text_field(stripcslashes($_REQUEST['endpoint'] ?? ''));
        $query = stripcslashes($_REQUEST['query'] ?? '');

        echo \Tigon\DmsConnect\Includes\DMS_Connector::request($query, $endpoint, 'POST');

        exit;
    }

    /**
     * Ajax function to convert a single cart to WP Format
     * @return never
     */
    public static function ajax_import_convert()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request
        // AJAX produces unwanted slashes
        $stripped = stripcslashes($_REQUEST['data'] ?? '');

        // convert_to_import accepts associative array
        $a_array = json_decode($stripped, true);
        if (!is_array($a_array)) {
            wp_send_json_error('Invalid data', 400);
        }

        if (!empty($a_array['pid'])) {
            $pid = absint($a_array['pid']);
            $product = wc_get_product($pid);
            if($product===false) {
                $a_array['pid'] = wc_get_product_id_by_sku($a_array['vinNo'] ?? $a_array['serialNo'] ?? '');
                $product = wc_get_product($a_array['pid']);
            }

            if($product) {
                $featured_image = get_post_thumbnail_id($pid);
                wp_delete_post($featured_image, true);

                $images = $product?->get_gallery_image_ids()??[];
                foreach ($images as $i) {
                    wp_delete_post($i, true);
                }

                $monroney_meta = get_post_meta($pid, 'monroney_sticker', true);
                if ($monroney_meta) {
                    $monroney_url = explode('"', $monroney_meta)[1] ?? '';
                    if ($monroney_url) {
                        $monroney = attachment_url_to_postid($monroney_url);
                        wp_delete_post($monroney, true);
                    }
                }
            } else $a_array['pid'] = null;
        }

        $used_cart = new \Tigon\DmsConnect\Admin\Used\Cart($a_array);

        $data = $used_cart->convert();

        // Use JSON instead of serialize() to prevent PHP object injection
        echo json_encode(['data' => json_encode($data->data)]);

        exit;
    }

    /**
     * Ajax function to convert a single cart to WP Format
     * @return never
     */
    public static function ajax_new_import_convert()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request
        // AJAX produces unwanted slashes
        $stripped = stripcslashes($_REQUEST['data'] ?? '');

        // convert_to_import accepts associative array
        $a_array = json_decode($stripped, true);
        if (!is_array($a_array)) {
            wp_send_json_error('Invalid data', 400);
        }

        $new_cart = new \Tigon\DmsConnect\Admin\New\Cart($a_array);

        $data = $new_cart->convert();

        echo json_encode($data);

        exit;
    }

    /**
     * Import single cart via Create Item
     * Cart must already exist
     * @return bool|string
     */
    public static function import_create(Database_Object $data)
    {
        // create_item returns associative array, client wants JSON
        return json_encode(Database_Write_Controller::create_from_database_object($data));
    }
    // Ajax wrapper for above
    public static function ajax_import_create()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request — uses JSON instead of PHP serialize
        $raw_data = stripcslashes($_REQUEST['data'] ?? '');
        $data = self::decode_database_object($raw_data);
        if (!$data) {
            wp_send_json_error('Invalid data', 400);
        }
        $result = Ajax_Import_Controller::import_create($data);
        echo $result;

        // Update PID on DMS
        $result = json_decode($result, true);

        $pid_request = json_encode([
            [
                '_id' => $data->get_value('_id'),
                'pid' => $result['pid'],
                'advertising' => [
                    'onWebsite' => true,
                    'websiteUrl' => $result['websiteUrl']
                ]
            ]
        ]);

        \Tigon\DmsConnect\Includes\DMS_Connector::request($pid_request, '/chimera/carts', 'PUT');

        exit;
    }

    /**
     * Import single cart via Update Item
     * Cart must already exist
     * @return bool|string
     */
    public static function import_update(Database_Object $data)
    {

        // update_item returns associative array, client wants JSON
        return json_encode(\Tigon\DmsConnect\Admin\Database_Write_Controller::update_from_database_object($data));
    }
    // Ajax wrapper for above
    public static function ajax_import_update()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request — uses JSON instead of PHP serialize
        $raw_data = stripcslashes($_REQUEST['data'] ?? '');
        $data = self::decode_database_object($raw_data);
        if (!$data) {
            wp_send_json_error('Invalid data', 400);
        }
        $result = Ajax_Import_Controller::import_update($data);
        echo $result;

        // Update PID on DMS
        $result = json_decode($result, true);

        $pid_request = json_encode([
            [
                '_id' => $data->get_value('_id'),
                'pid' => $result['pid'],
                'advertising' => [
                    'onWebsite' => true,
                    'websiteUrl' => $result['websiteUrl']
                ]
            ]
        ]);

        \Tigon\DmsConnect\Includes\DMS_Connector::request($pid_request, '/chimera/carts', 'PUT');

        exit;
    }

    // Ajax wrapper for above
    public static function ajax_import_delete()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request — uses JSON instead of PHP serialize
        $raw_data = stripcslashes($_REQUEST['data'] ?? '');
        $data = self::decode_database_object($raw_data);
        if (!$data) {
            wp_send_json_error('Invalid data', 400);
        }

        echo Ajax_Import_Controller::import_delete($data);

        exit;
    }

    public static function ajax_import_new()
    {
        self::verify_ajax_request();
        ignore_user_abort(true);
        header("Content-Type: application/json; charset=utf-8", true);

        // Data from AJAX request
        // AJAX produces unwanted slashes
        $data = stripcslashes($_REQUEST['data'] ?? '');
        $data = json_decode($data, true);
        if (!is_array($data)) {
            wp_send_json_error('Invalid data', 400);
        }

        $forced_fields = 0;
        if($_REQUEST['forced']??false) {
            $forced_fields = stripcslashes($_REQUEST['forced']);
            $forced_fields = json_decode($forced_fields);
            if (is_array($forced_fields)) {
                Product_Fields::define_constants();
                // Allowlist constant names to prevent arbitrary constant access
                $forced_fields = array_filter($forced_fields, function($value) {
                    return in_array($value, self::$allowed_field_constants, true);
                });
                $forced_fields = array_map(function($value) {
                    return constant($value);
                }, $forced_fields);
                $forced_fields = Product_Fields::combine_options(...$forced_fields);
            } else {
                $forced_fields = 0;
            }
        }

        $result = Ajax_Import_Controller::import_new($data, $forced_fields);
        echo json_encode($result);
        if(is_wp_error($result)) {
            error_log('Tigon DMS Connect error: ' . json_encode($result));
            exit;
        }

        // Update PID on DMS for all similar carts — use json_encode instead of string interpolation
        $dms_filter = json_encode([
            'make' => $data['cartType']['make'] ?? '',
            'model' => $data['cartType']['model'] ?? '',
            'year' => $data['cartType']['year'] ?? '',
            'cartColor' => $data['cartAttributes']['cartColor'] ?? '',
            'seatColor' => $data['cartAttributes']['seatColor'] ?? '',
            'locationId' => $data['cartLocation']['locationId'] ?? '',
        ]);
        $new_pids = \Tigon\DmsConnect\Includes\DMS_Connector::request($dms_filter, '/chimera/lookup', 'POST');
        $new_pids = json_decode($new_pids, true) ?? [];

        $new_pids = array_map(function($cart) use ($result) {
            return [
                '_id' => $cart['_id'] ?? '',
                'pid' => $result['pid'] ?? '',
            ];
        }, $new_pids);

        \Tigon\DmsConnect\Includes\DMS_Connector::request(json_encode($new_pids), '/chimera/carts', 'PUT');

        // Update oldPid if set
        if(!empty($result['oldPid']) && !empty($result['dmsSelector'])) {
            $update_pids = json_decode(\Tigon\DmsConnect\Includes\DMS_Connector::request($result['dmsSelector'], '/chimera/lookup', 'POST'), true) ?? [];

            $update_pids = array_map(function($cart) use ($result) {
                return [
                    '_id' => $cart['_id'] ?? '',
                    'pid' => $result['oldPid'] ?? '',
                    'advertising' => [
                        'websiteUrl' => $result['updateUrl'] ?? '',
                    ],
                ];
            }, $update_pids);

            \Tigon\DmsConnect\Includes\DMS_Connector::request(json_encode($update_pids), '/chimera/carts', 'PUT');
        }

        exit;
    }
}
