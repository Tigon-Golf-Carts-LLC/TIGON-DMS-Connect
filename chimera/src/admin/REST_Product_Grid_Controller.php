<?php

namespace Tigon\Chimera\Admin;

use \Tigon\Chimera\Includes\Utilities as U;

class REST_Product_Grid_Controller
{
    private function __construct()
    {
    }

    /**
     * Sets the products displayed in a given product grid
     *
     * @param integer $landing_page
     * @param array $data
     * @return void
     */
    public static function set(
        int $landing_page,
        int $archive,
        array $data,
        String $location_name,
        array $archive_not_in = array()
    )
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Get lists as associative array
        if(isset($data) && !empty($data)){
            foreach($data as $data_value){
                $cart[$data_value['key']]=$data_value['carts'];
            }
            $data = $cart;
        }

        // Fill grids
        $location_slug = str_replace('tigon_', '', $location_name);
        $location_slug = str_replace('_', '-', $location_slug);
        $full_data['featuredNewCarts'] = self::replenish_grid($data['featuredNewCarts'], 8, ['new', $location_slug]);
        $full_data['featuredUsedCarts'] = self::replenish_grid($data['featuredUsedCarts'], 8, ['used', $location_slug]);
        $full_data['popularCarts'] = self::replenish_grid($data['featuredUsedCarts'], 4, [$location_slug]);

        // Handle archive popular grids
        if($archive==0){
            $popular_changed = self::national_popular($archive_not_in, $data, $location_name);
        }else{
            $popular_changed = $elementor = self::location_popular($archive, $data, $location_name);
        }
        
        // Get landing page elementor data
        if($location_name === 'national') {
            $set_locations = $wpdb->get_col(
                'SELECT REPLACE(lists.location_name, "tigon_", "")
                FROM '.$wpdb->prefix.'chimera_cart_lists AS lists
                GROUP BY lists.location_name;'
            );

            $like_statements = array_map(function($e) {
                return "posts.post_name NOT LIKE '%$e%'";
            }, $set_locations);
            $like_statements = implode(' AND ', $like_statements);
            
            $unset_landing_pages = $wpdb->get_col(
                'SELECT posts.ID FROM '.$wpdb->prefix.'posts AS posts
                WHERE posts.post_type = "page" AND posts.post_title LIKE "New % Used Golf Carts%"
                AND ('.$like_statements.')'
            );
            $unset_landing_pages = array_map(function($e) {
                return intval($e);
            }, $unset_landing_pages);

            $targets = [$landing_page, ...$unset_landing_pages];
            foreach($targets as $page) {
                ['elementor'=>$elementor, 'meta_id'=>$meta_id] = self::get_elementor_data($page);
                // Set landing page new/used grids
                self::new_used_grid($full_data, $elementor);
    
                // Save updated elementor data to database
                $elementor = json_encode($elementor);
                $new_used_changed = 0;
                $new_used_changed += $wpdb->update($wpdb->prefix . 'postmeta', ['meta_value' => $elementor], ['meta_id' => $meta_id]);
            }
        } else {
            ['elementor'=>$elementor, 'meta_id'=>$meta_id] = self::get_elementor_data($landing_page);
            
            // Set landing page new/used grids
            self::new_used_grid($full_data, $elementor);

            // Save updated elementor data to database
            $elementor = json_encode($elementor);
            $new_used_changed = $wpdb->update($wpdb->prefix . 'postmeta', ['meta_value' => $elementor], ['meta_id' => $meta_id]);
        }

        // Force update page
        wp_update_post([
            'ID' => $landing_page
        ]);

        // Save lists to db for archive ordering
        $archive_changed = 0;
        foreach($data as $key => $list) {
            $archive_changed += self::insert_chimera_cart_list($location_name, $key, $list);
        }

        return [
            'updatedPopular' => boolval($popular_changed),
            'updatedNewUsed' => boolval($new_used_changed),
            'updatedArchive' => boolval($archive_changed)
        ];
    }

    /**
     * Places the new and used lists into their respective elementor grids
     *
     * @param Array $data Data array containing featuredNewCarts, featuredUsedCarts
     * @param Array $elementor Passed by reference, the elementor data to be modified
     * @return void
     */
    public static function new_used_grid(Array $data, Array &$elementor) {
        
        if (isset($data['featuredNewCarts'])) {
            $new_address = U::array_deepfind($elementor, '_element_id', 'new-grid');
        } else $new_address = false;

        if (isset($data['featuredUsedCarts'])) {
            $used_address = U::array_deepfind($elementor, '_element_id', 'used-grid');
        } else $used_address = false;  

        // Set new carts grid
        if ($new_address !== false) {
            $new_grid = &U::array_access($elementor, ...$new_address);

            $new_pids = array_map(function ($pid) {
                return strval($pid);
            }, $data['featuredNewCarts']);

            $new_grid['eael_product_grid_products_in'] = $new_pids;
            $new_grid['eael_product_grid_product_filter'] = 'manual';
            $new_grid['orderby'] = 'menu_order';
        }

        // Set used carts grid
        if ($used_address !== false) {
            $used_grid = &U::array_access($elementor, ...$used_address);

            $used_pids = array_map(function ($pid) {
                return strval($pid);
            }, $data['featuredUsedCarts']);

            $used_grid['eael_product_grid_products_in'] = $used_pids;
            $used_grid['eael_product_grid_product_filter'] = 'manual';
            $used_grid['orderby'] = 'menu_order';
        }
    }

    /**
     * Get elementor data and id of row for a given page
     *
     * @param Int $page_id Post ID of the desired data's page
     * @return void
     */
    public static function get_elementor_data($page_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . "postmeta";

        $meta_id = $wpdb->get_var("SELECT meta_id FROM $table_name WHERE post_id = '$page_id' AND meta_key = '_elementor_data'");

        $elementor = $wpdb->get_var("SELECT meta_value FROM $table_name WHERE meta_id = '$meta_id'");
        $elementor = json_decode($elementor, true)??[];
        return array('elementor'=>$elementor,'meta_id'=>$meta_id);  
    }

    /**
     * Set the popular grid on a single archive
     *
     * @param Int $page_id
     * @param Array $data
     * @param String $location_name
     * @return mixed
     */
    public static function location_popular(Int $page_id, Array $data, String $location_name ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "postmeta";

        $location = explode('_', $location_name);
        if(!empty($location) && isset($location[1])){
            $location_name = $location[1];
        }

        // Get elementor data for archive page
        ['elementor'=>$elementor, 'meta_id'=>$meta_id] = self::get_elementor_data($page_id, $table_name);

        if (isset($data['popularCarts'])) {
            $popular_address = U::array_deepfind($elementor, '_element_id', 'popular-grid');
            // Set popular carts grid
            if ($popular_address !== false) {
                $popular_grid = &U::array_access($elementor, ...$popular_address);
     
                $popular_pids = array_map(function ($pid) {
                    return strval($pid);
                }, $data['popularCarts']);

                $popular_grid['eael_product_grid_products_in'] = $popular_pids;
                $popular_grid['eael_product_grid_product_filter'] = 'manual';
                $popular_grid['orderby'] = 'menu_order';
                self::insert_chimera_cart_list($location_name,'popular',$popular_pids);
            }
        }
        // Save updated elementor data to database

        $elementor = json_encode($elementor);
        $result = $wpdb->update($table_name, ['meta_value' => $elementor], ['meta_id' => $meta_id]);
        return $result;
    }

    /**
     * Set the popular grid on all but the specified archives
     *
     * @param Int $page_id
     * @param Array $data
     * @param String $location_name
     * @return mixed
     */
    public static function national_popular(Array $archive_not_in, Array $data, String $location_name) {
        // Get elementor data of the page for all product archive
        global $wpdb;
        $table_name = $wpdb->prefix . "postmeta";
        $table_posts = $wpdb->prefix . "posts";

        $meta_id_product_archive = $wpdb->get_results("SELECT pm.post_id, p.post_title FROM $table_name as pm JOIN $table_posts as p ON p.id=pm.post_id WHERE pm.meta_value = 'product-archive' AND pm.meta_key = '_elementor_template_type' AND p.id NOT IN ( '" . implode( "', '" , $archive_not_in ) . "' )",ARRAY_A);

        $post_ids = implode(',',array_column($meta_id_product_archive, 'post_id'));

        $meta_ids = $wpdb->get_results("SELECT meta_id FROM $table_name WHERE post_id IN (".$post_ids.") AND meta_key = '_elementor_data'",ARRAY_A);
        $meta_ids_implode = implode(',',array_column($meta_ids, 'meta_id'));

        $elementor_array = $wpdb->get_results("SELECT meta_value,meta_id FROM $table_name WHERE meta_id IN (".$meta_ids_implode.")",ARRAY_A);

        if (isset($data['popularCarts']) && !empty($elementor_array)) {
            foreach($elementor_array as $key=>$value){
                $elementor_value = json_decode($value['meta_value'], true)??[];
                 
                $popular_address = U::array_deepfind($elementor_value, '_element_id', 'popular-grid');

                // Set popular carts grid
                if ($popular_address !== false) {
                    $popular_grid = &U::array_access($elementor_value, ...$popular_address);
         
                    $popular_pids = array_map(function ($pid) {
                        return strval($pid);
                    }, $data['popularCarts']);

                    $popular_grid['eael_product_grid_products_in'] = $popular_pids;
                    $popular_grid['eael_product_grid_product_filter'] = 'manual';
                    $popular_grid['orderby'] = 'menu_order';
                }

                // Save updated elementor data to database
                $elementor_value = json_encode($elementor_value);
                $result = $wpdb->update($table_name, ['meta_value' => $elementor_value], ['meta_id' => $value['meta_id']]);
               
            }
        } else $popular_address = false;
        return $result;
    }

    /**
     * Save list to the chimera_cart_lists table
     *
     * @param String $location_name
     * @param String $list_name
     * @param Array $ids
     * @return Int|Boolean
     */
    public static function insert_chimera_cart_list(String $location_name, String $list_name, Array $ids){
        global $wpdb;
        $insert_table = $wpdb->prefix . "chimera_cart_lists";

        //Insert new data in table 'dev_chimera_cart_lists'
        $value_check = $wpdb->query("SELECT * FROM $insert_table WHERE location_name = '$location_name' AND list_name = '$list_name'");

        array_filter($ids);

        if($value_check>0){
            $data = array(
                'list' => json_encode($ids)
            );
            $where = array(
                'location_name' => $location_name,
                'list_name' => $list_name
            );

            $result = $wpdb->update( $insert_table, $data, $where );

        }else{
            $data = array(
                'location_name' => $location_name,
                'list_name' => $list_name,
                'list' => json_encode($ids)
            );

            $result = $wpdb->insert( $insert_table, $data );
        }
        return $result;
    }

    /**
     * Accepts a list of product IDs, the desired number of products, and the filters,
     * and returns a refilled list of IDs.
     *
     * @return void
     */
    private static function replenish_grid(array $list, int $size, array $filters) {
        $filters = array_filter($filters, function($e) {return $e != 'national';});

        global $wpdb;
        $posts = $wpdb->prefix . 'posts';
        $postmeta = $wpdb->prefix . 'postmeta';
        $term_relationships = $wpdb->prefix . 'term_relationships';
        $terms = $wpdb->prefix . 'terms';

        // posts                -> p
        // postmeta             -> m
        // term_relationships   -> r
        // terms                -> t
        // aggregation          -> a
        $columns = [
            'p.ID',
            'p.post_status',
            'MAX(CASE WHEN t.slug LIKE "local-new-active-inventory" OR t.slug LIKE "local-used-active-inventory" THEN TRUE ELSE FALSE END) as local'
        ];
        $conditions = [
            'a.ID NOT IN ('.implode(', ', $list).')',
            'a.post_status = "publish"',
            'a.local = TRUE'
        ];

        foreach($filters as $filter) {
            $column = "MAX(CASE WHEN t.slug = '$filter' THEN TRUE ELSE FALSE END) as $filter";
            array_push($columns, $column);

            $condition = "a.$filter = TRUE";
            array_push($conditions, $condition);
        }

        $columns = implode(', ', $columns);
        $conditions = implode(' AND ', $conditions);

        $limit = $size - count($list);

        $query = "SELECT * FROM
        (
            SELECT $columns
        
            FROM $posts AS p 
            JOIN $term_relationships AS r ON p.ID = r.object_id
            JOIN $terms AS t ON r.term_taxonomy_id = t.term_id
            RIGHT JOIN $postmeta AS m ON p.ID = m.post_id AND m.meta_key = '_stock_status'
        
            WHERE post_type = 'product'
            AND m.meta_value = 'instock'
            GROUP BY p.ID
        ) AS a
        WHERE $conditions
        LIMIT $limit;";

        $additions = $wpdb->get_col($query);
        array_push($list, ...$additions);

        return $list;
    }
}
