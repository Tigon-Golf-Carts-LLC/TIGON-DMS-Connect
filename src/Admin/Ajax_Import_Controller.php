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
     * Ajax wrapper for get_db
     * @return never
     */
    public static function query_dms()
    {
        ignore_user_abort(true);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $endpoint = stripcslashes($_REQUEST['endpoint']);
            $query = stripcslashes($_REQUEST['query']);

            echo \Tigon\DmsConnect\Includes\DMS_Connector::request($query, $endpoint, 'POST');
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        exit;
    }

    /**
     * Ajax function to convert a single cart to WP Format
     * @return never
     */
    public static function ajax_import_convert()
    {
        ignore_user_abort(true);


        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $stripped = stripcslashes($_REQUEST['data']);

            // convert_to_import accepts associative array
            $a_array = json_decode($stripped, true);

            

            if ($a_array['pid']) {
                $product = wc_get_product($a_array['pid']);
                if($product===false) {
                    $request['pid'] = wc_get_product_id_by_sku($a_array['vinNo']?$a_array['vinNo']:$a_array['serialNo']);
                    $product = wc_get_product(
                        $a_array['pid']
                    );
                }

                if($product) {
                    $featured_image = get_post_thumbnail_id($a_array['pid']);
                    wp_delete_post($featured_image, true);

                    $images = $product?->get_gallery_image_ids()??[];
                    foreach ($images as $i) {
                        wp_delete_post($i, true);
                    }

                } else $a_array['pid'] = null;
            }

            $used_cart = new \Tigon\DmsConnect\Admin\Used\Cart($a_array);

            $data = $used_cart->convert();

            echo json_encode(['data' => serialize($data)]);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        exit;
    }

    /**
     * Ajax function to convert a single cart to WP Format
     * @return never
     */
    public static function ajax_new_import_convert()
    {
        ignore_user_abort(true);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $stripped = stripcslashes($_REQUEST['data']);

            // convert_to_import accepts associative array
            $a_array = json_decode($stripped, true);

            $new_cart = new \Tigon\DmsConnect\Admin\New\Cart($a_array);

            $data = $new_cart->convert();

            echo json_encode($data);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

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
        ignore_user_abort(true);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $data = stripcslashes($_REQUEST['data']);
            $data = unserialize($data);
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
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

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
        ignore_user_abort(true);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $data = stripcslashes($_REQUEST['data']);
            $data = unserialize($data);
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
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        exit;
    }
    
    // Ajax wrapper for above
    public static function ajax_import_delete()
    {
        ignore_user_abort(true);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $data = stripcslashes($_REQUEST['data']);
            $data = unserialize($data);

            echo Ajax_Import_Controller::import_delete($data);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        exit;
    }

    public static function ajax_import_new()
    {
        ignore_user_abort(true);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header("Content-Type: application/json; charset=utf-8", true);

            // Data from AJAX request
            // AJAX produces unwanted slashes
            $data = stripcslashes($_REQUEST['data']);
            $data = json_decode($data, true);

			$forced_fields = 0;
            if($_REQUEST['forced']??false) {
                $forced_fields = stripcslashes($_REQUEST['forced']);
                $forced_fields = json_decode($forced_fields);
                Product_Fields::define_constants();
                $forced_fields = array_map(function($value) {
                    return constant($value);
                }, $forced_fields);
                $forced_fields = Product_Fields::combine_options(...$forced_fields);
            }

            $result = Ajax_Import_Controller::import_new($data, $forced_fields);
            echo json_encode($result);
            if(is_wp_error($result)) {
                error_log('Tigon DMS Connect error: ' . json_encode($result));
                exit;
            }

            // Update PID on DMS for all similar carts
            $dms_filter = '{
                "make": "' . $data['cartType']['make'] . '",
                "model": "' . $data['cartType']['model'] . '",
                "year": "' . $data['cartType']['year'] . '",
                "cartColor": "' . $data['cartAttributes']['cartColor'] . '",
                "seatColor": "' . $data['cartAttributes']['seatColor'] . '",
                "locationId": "' . $data['cartLocation']['locationId'] . '"
            }';
            $new_pids = \Tigon\DmsConnect\Includes\DMS_Connector::request($dms_filter, '/chimera/lookup', 'POST');
            $new_pids = json_decode($new_pids, true);

            $new_pids = array_map(function($cart) use ($result) {
                $query = '{
                    "_id": "'.$cart['_id'].'",
                    "pid": "'.$result['pid'].'"
                }';
                return $query;
            }, $new_pids);

            \Tigon\DmsConnect\Includes\DMS_Connector::request('['.implode(',', $new_pids).']', '/chimera/carts', 'PUT');

            // Update oldPid if set
            if($result['oldPid']) {
                $update_pids = json_decode(\Tigon\DmsConnect\Includes\DMS_Connector::request($result['dmsSelector'], '/chimera/lookup', 'POST'), true);

                $update_pids = array_map(function($cart) use ($result) {
                    $query = '{
                        "_id": "'.$cart['_id'].'",
                        "pid": "'.$result['oldPid'].'",
                        "advertising": {
                            "websiteUrl": "'.$result['updateUrl'].'"
                        }
                    }';
                    return $query;
                }, $update_pids);

                \Tigon\DmsConnect\Includes\DMS_Connector::request('['.implode(',', $update_pids).']', '/chimera/carts', 'PUT');
            }
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        exit;
    }

    private static function product_is_static(int $pid): bool
    {
        if (!$pid) return false;

        $post = get_post($pid);
        if ($post && $post->post_type === 'static') return true;

        $terms = get_the_terms($pid, 'added-features') ?: [];
        foreach ($terms as $term) {
            if (in_array(strtoupper($term->name), ['STATIC STOCK', 'STATIC INVENTORY'])) return true;
        }

        return false;
    }

    private static function import_used_cart(array $cart)
    {
        $used_cart = new \Tigon\DmsConnect\Admin\Used\Cart($cart);
        $converted = $used_cart->convert();
        if (is_wp_error($converted)) return $converted;

        if ($converted->get_value('method') == 'create') {
            $result = Database_Write_Controller::create_from_database_object($converted);
        } else if ($converted->get_value('method') == 'update') {
            $result = Database_Write_Controller::update_from_database_object($converted);
        } else {
            $result = Database_Write_Controller::delete_by_id($converted);
        }
        if (is_wp_error($result)) return $result;

        if (!empty($result['pid']) && !empty($cart['cartLocation']['locationId'])) {
            self::apply_location_meta((int)$result['pid'], $cart['cartLocation']['locationId']);
        }

        return $result;
    }

    private static function run_inventory_refresh(bool $create_missing = true): array
    {
        $stats = ['new' => 0, 'used' => 0, 'created' => 0, 'updated' => 0, 'skipped_static' => 0, 'skipped_missing' => 0, 'errors' => 0];
        $new_carts = json_decode(\Tigon\DmsConnect\Includes\DMS_Connector::request('{"isUsed":false,"needOnWebsite":true,"isInStock":true,"isInBoneyard":false}', '/chimera/lookup', 'POST'), true) ?? [];
        $used_carts = json_decode(\Tigon\DmsConnect\Includes\DMS_Connector::request('{"isUsed":true,"needOnWebsite":true,"isInStock":true,"isInBoneyard":false}', '/chimera/lookup', 'POST'), true) ?? [];

        foreach ($new_carts as $cart) {
            $stats['new']++;
            $pid = intval($cart['pid'] ?? 0);
            if ($pid && self::product_is_static($pid)) {
                $stats['skipped_static']++;
                continue;
            }
            if (!$create_missing && !$pid) {
                $stats['skipped_missing']++;
                continue;
            }
            $result = self::import_new($cart);
            if (is_wp_error($result)) {
                $stats['errors']++;
                continue;
            }
            if ($pid) $stats['updated']++; else $stats['created']++;
        }

        foreach ($used_carts as $cart) {
            $stats['used']++;
            $sku = $cart['vinNo'] ?: $cart['serialNo'];
            $pid = intval(wc_get_product_id_by_sku($sku));
            if ($pid && self::product_is_static($pid)) {
                $stats['skipped_static']++;
                continue;
            }
            if (!$create_missing && !$pid) {
                $stats['skipped_missing']++;
                continue;
            }
            $cart['pid'] = $pid;
            $result = self::import_used_cart($cart);
            if (is_wp_error($result)) {
                $stats['errors']++;
                continue;
            }
            if ($pid) $stats['updated']++; else $stats['created']++;
        }

        return $stats;
    }

    public static function refresh_active_inventory()
    {
        header("Content-Type: application/json; charset=utf-8", true);
        echo json_encode(['success' => true, 'stats' => self::run_inventory_refresh(false)]);
        exit;
    }

    public static function repull_dms_inventory()
    {
        header("Content-Type: application/json; charset=utf-8", true);
        echo json_encode(['success' => true, 'stats' => self::run_inventory_refresh(true)]);
        exit;
    }
}
