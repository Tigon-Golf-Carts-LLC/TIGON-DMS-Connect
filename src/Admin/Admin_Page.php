<?php

namespace Tigon\DmsConnect\Admin;

use Tigon\DmsConnect\Includes\DMS_Connector;
use Tigon\DmsConnect\Includes\Product_Fields;

class Admin_Page
{

    private function __construct()
    {
    }

    /**
     * Add Tigon DMS Connect to Admin Sidebar
     * @return void
     */
    public static function add_menu_page()
    {
        $svg = file_get_contents(TIGON_DMS_PLUGIN_DIR . 'assets/images/tigondb.svg');
        $data_uri = 'data:image/svg+xml;base64,' . base64_encode($svg);

        $page_title = "Tigon DMS Connect";
        $menu_title = "DMS Connect";
        $capability = "manage_options";
        $menu_slug = "tigon-dms-connect";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::diagnostic_page';
        $icon_url = $data_uri;
        $position = 55;
        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url, $position);
        self::add_import_page();
        self::add_grids_page();
        self::add_sync_page();
        self::add_settings_page();
    }

    /**
     * Add Import submenu
     * @return void
     */
    public static function add_import_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title = "Tigon DMS Import";
        $menu_title = "Import";
        $capability = "manage_options";
        $menu_slug = "import";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::import_page';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    /**
     * Add Grids submenu
     * @return void
     */
    public static function add_grids_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title = "DMS Grids";
        $menu_title = "Grids";
        $capability = "manage_options";
        $menu_slug = "dms-grids";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::grids_page';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    /**
     * Render the Grids admin page.
     * Shows cached product IDs for each grid (New, Used, Popular) per location.
     * Locations are loaded dynamically from the database — no hardcoded list.
     * Provides individual Refresh buttons that call the DMS API on demand.
     */
    public static function grids_page()
    {
        $nonce = wp_create_nonce('dms_grids_nonce');

        // Build locations dynamically from wp_options (dms_grid_cache_*) and cart_lists table
        $locations = self::get_grid_locations();

        $grid_types = array(
            'new'     => array('label' => 'New Carts',     'api_key' => 'featuredNewCarts'),
            'used'    => array('label' => 'Used Carts',    'api_key' => 'featuredUsedCarts'),
            'popular' => array('label' => 'Popular Carts', 'api_key' => 'popularCarts'),
        );

        self::page_header();

        echo '<div class="body" style="flex-direction:column;">';
        echo '<p style="margin:0 0 1.5rem;color:#666;">Product grids are cached locally. They only update when <strong>DMS pushes a change</strong> or you click <strong>Refresh</strong> below. No API calls happen on page load.</p>';

        if (empty($locations)) {
            echo '<div class="action-box primary"><p>No grid data cached yet. Use the form below to refresh a location, or wait for DMS to push data.</p></div>';
        }

        foreach ($locations as $loc_key => $loc_label) {
            $option_key = 'dms_grid_cache_' . sanitize_key($loc_key);
            $cached = get_option($option_key, false);

            echo '<div class="action-box-group" style="margin-bottom:2rem;">';
            echo '<div class="action-box primary" style="flex-direction:column;">';
            echo '<h2>' . esc_html($loc_label) . '</h2>';

            foreach ($grid_types as $type_key => $type_info) {
                $count = 0;
                if ($cached && !empty($cached['data'])) {
                    foreach ($cached['data'] as $section) {
                        if ($section['key'] === $type_info['api_key']) {
                            $carts = $section['carts'] ?? array();
                            $count = count($carts);
                        }
                    }
                }

                echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid #eee;">';
                echo '<div>';
                echo '<strong>' . esc_html($type_info['label']) . '</strong>';
                echo ' <span style="color:#888;">(' . $count . ' carts cached)</span>';
                echo '</div>';
                echo '<button class="button dms-grid-refresh" data-location="' . esc_attr($loc_key) . '" data-grid="' . esc_attr($type_key) . '" data-nonce="' . $nonce . '">Refresh ' . esc_html($type_info['label']) . '</button>';
                echo '</div>';
            }

            // Refresh All button for this location
            echo '<div style="margin-top:1rem;">';
            echo '<button class="button button-primary dms-grid-refresh" data-location="' . esc_attr($loc_key) . '" data-grid="all" data-nonce="' . $nonce . '">Refresh All Grids for ' . esc_html($loc_label) . '</button>';
            echo '</div>';

            echo '</div>'; // .action-box
            echo '</div>'; // .action-box-group
        }

        // Manual refresh for any location key
        echo '<div class="action-box-group" style="margin-bottom:2rem;">';
        echo '<div class="action-box primary" style="flex-direction:column;">';
        echo '<h2>Refresh by Location Key</h2>';
        echo '<p style="color:#666;margin:0 0 1rem;">Enter a DMS location key (e.g. <code>national</code>, <code>tigon_dover</code>, <code>tigon_raleigh</code>) to fetch and cache its grids.</p>';
        echo '<div style="display:flex;gap:0.5rem;align-items:center;">';
        echo '<input type="text" id="dms-grid-manual-location" placeholder="tigon_location_name" style="flex:1;padding:0.4rem 0.6rem;" />';
        echo '<button class="button button-primary" id="dms-grid-manual-refresh" data-nonce="' . $nonce . '">Refresh</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div id="dms-grids-result" style="margin-top:1rem;"></div>';
        echo '</div>'; // .body

        // Inline JS for AJAX refresh
        ?>
        <script>
        (function($) {
            $(document).on('click', '.dms-grid-refresh', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var loc = $btn.data('location');
                var grid = $btn.data('grid');
                var nonce = $btn.data('nonce');
                var originalText = $btn.text();

                $btn.prop('disabled', true).text('Refreshing...');
                $('#dms-grids-result').html('');

                $.post(globals.ajaxurl, {
                    action: 'dms_refresh_grid',
                    location: loc,
                    grid_type: grid,
                    nonce: nonce
                }, function(response) {
                    $btn.prop('disabled', false).text(originalText);
                    if (response.success) {
                        $('#dms-grids-result').html('<div class="notice notice-success" style="padding:0.75rem;"><p>' + response.data.message + '</p></div>');
                        setTimeout(function() { window.location.reload(); }, 1000);
                    } else {
                        $('#dms-grids-result').html('<div class="notice notice-error" style="padding:0.75rem;"><p>' + (response.data || 'Refresh failed') + '</p></div>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text(originalText);
                    $('#dms-grids-result').html('<div class="notice notice-error" style="padding:0.75rem;"><p>Network error. Please try again.</p></div>');
                });
            });

            // Manual location refresh
            $('#dms-grid-manual-refresh').on('click', function(e) {
                e.preventDefault();
                var loc = $('#dms-grid-manual-location').val().trim();
                if (!loc) { alert('Enter a location key'); return; }
                var $btn = $(this);
                var nonce = $btn.data('nonce');
                $btn.prop('disabled', true).text('Refreshing...');
                $('#dms-grids-result').html('');

                $.post(globals.ajaxurl, {
                    action: 'dms_refresh_grid',
                    location: loc,
                    grid_type: 'all',
                    nonce: nonce
                }, function(response) {
                    $btn.prop('disabled', false).text('Refresh');
                    if (response.success) {
                        $('#dms-grids-result').html('<div class="notice notice-success" style="padding:0.75rem;"><p>' + response.data.message + '</p></div>');
                        setTimeout(function() { window.location.reload(); }, 1000);
                    } else {
                        $('#dms-grids-result').html('<div class="notice notice-error" style="padding:0.75rem;"><p>' + (response.data || 'Refresh failed') + '</p></div>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('Refresh');
                    $('#dms-grids-result').html('<div class="notice notice-error" style="padding:0.75rem;"><p>Network error. Please try again.</p></div>');
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Get all known grid locations dynamically from the database.
     * Merges locations from wp_options (dms_grid_cache_*) and the cart_lists table.
     *
     * @return array Associative array of location_key => display_label
     */
    private static function get_grid_locations()
    {
        global $wpdb;
        $locations = array();

        // 1. Get locations from wp_options (dms_grid_cache_*)
        $option_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'dms_grid_cache_%' ORDER BY option_name"
        );
        foreach ($option_keys as $opt) {
            $loc_key = str_replace('dms_grid_cache_', '', $opt);
            $locations[$loc_key] = true;
        }

        // 2. Get locations from tigon_dms_cart_lists table
        $table = $wpdb->prefix . 'tigon_dms_cart_lists';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $list_locs = $wpdb->get_col("SELECT DISTINCT location_name FROM $table ORDER BY location_name");
            foreach ($list_locs as $loc) {
                $locations[$loc] = true;
            }
        }

        // Build display labels from keys
        $result = array();

        // Always put national first if it exists
        if (isset($locations['national'])) {
            $result['national'] = 'National (Homepage)';
            unset($locations['national']);
        }

        // Convert remaining keys to readable labels
        ksort($locations);
        foreach ($locations as $key => $v) {
            $label = $key;
            // Strip tigon_ prefix
            $label = preg_replace('/^tigon_/', '', $label);
            // Convert underscores/hyphens to spaces and title-case
            $label = ucwords(str_replace(array('_', '-'), ' ', $label));
            $result[$key] = $label;
        }

        return $result;
    }

    /**
     * Add Sync submenu
     * @return void
     */
    public static function add_sync_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title = "DMS Inventory Sync";
        $menu_title = "Sync";
        $capability = "manage_options";
        $menu_slug = "dms-inventory-sync";
        $callback = 'tigon_dms_sync_page';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    /**
     * Add Settings submenu
     * @return void
     */
    public static function add_settings_page()
    {
        $parent_slug = "tigon-dms-connect";
        $page_title = "Tigon DMS Settings";
        $menu_title = "Settings";
        $capability = "manage_options";
        $menu_slug = "settings";
        $callback = 'Tigon\DmsConnect\Admin\Admin_Page::settings_page';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback);
    }

    public static function page_header()
    {
        $css = file_get_contents(__DIR__ . '/../../assets/css/admin.css');
        $svg = preg_replace(
            '/#FFFFFF/',
            '#F4F4F9',
            file_get_contents(__DIR__ . '/../../assets/images/tigondb.svg')
        );

        echo '
        <meta name="viewport" content="height=device-height, width=device-width, initial-scale=1.0, minimum-scale=1.0, target-densitydpi=device-dpi">
        <style>' . $css . '</style>

        <div class="header">
            <span class="tigon-dms-icon">' . $svg . '</span>
            <h1>Tigon DMS Connect</h1>
        </div>';
    }

    public static function diagnostic_page()
    {
    ### Database Operations ###

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        
        // Get new carts of inventory type ACTIVE NEW INVENTORY as Slugs
        $new_carts = $wpdb->get_results('
            SELECT post_name
            FROM `'.$wpdb->prefix.'posts`
            WHERE ID IN
            (
                SELECT object_id FROM '.$wpdb->prefix.'term_relationships
                WHERE term_taxonomy_id = 4549
            )
            AND ID IN
            (
                SELECT post_id FROM '.$wpdb->prefix.'postmeta
                WHERE meta_key = "_stock_status"
                AND meta_value = "instock"
            )
            ORDER BY ID ASC;
        ');
        $new_carts = array_map(function($cart) {
            return $cart->post_name;
        }, $new_carts);

        // Get used carts of inventory type ACTIVE USED INVENTORY as SKUs
        $used_carts = $wpdb->get_results('
            SELECT meta_value
            FROM `'.$wpdb->prefix.'postmeta`
            WHERE post_id IN
            (
                SELECT * FROM
                (
                    SELECT object_id FROM
                    `'.$wpdb->prefix.'term_relationships` WHERE '.$wpdb->prefix.'term_relationships.term_taxonomy_id = 4553
                ) AS subquery
            )
            AND meta_key = "_sku";
        ');
        $used_carts = array_map(function($cart) {
            return $cart->meta_value;
        }, $used_carts);

    ### New Operations ###

        // Get new cart data from DMS
        // Result is encoded to string for array_unique
        $dms_new = json_decode(DMS_Connector::request('{"isUsed":false, "needOnWebsite": true, "isInStock": true, "isInBoneyard": false}', '/chimera/lookup', 'POST'), true)??[];
        $dms_new_unique = array_map(function($cart) {
            if (str_contains(strtoupper($cart['serialNo']), 'DELETE') || str_contains(strtoupper($cart['vinNo']), 'DELETE'))
                return '--DELETE--';

            $location_id = $cart['cartLocation']['locationId'];
            $city = Attributes::$locations[$location_id]['city_short']??Attributes::$locations[$location_id]['city'];

            $make = preg_replace('/\s+/', '-', trim(preg_replace('/\+/', ' plus ', $cart['cartType']['make'])));
            $model = preg_replace('/\s+/', '-', trim(preg_replace('/\+/', ' plus ', $cart['cartType']['model'])));
            $color = preg_replace('/\s+/', '-', $cart['cartAttributes']['cartColor']);
            $seat = preg_replace('/\s+/', '-', $cart['cartAttributes']['seatColor']);
            $location = preg_replace('/\s+/', '-', $city . "-" . Attributes::$locations[$location_id]['st']);
            $year = preg_replace('/\s+/', '-', $cart['cartType']['year']);
            return json_encode(['url' => strtolower("$make-$model-$color-seat-$seat-$location"), 'year' => $year]);
        }, $dms_new);
        // Remove invalid carts
        $dms_new_unique = array_filter($dms_new_unique, function($cart) {
            return ($cart !== '--DELETE--');
        });
        // Remove duplicate cart types
        $dms_new_unique = array_unique($dms_new_unique);

        // Decode filtered carts so we can work with their data
        $dms_new_unique = array_map(function($cart) {
            return json_decode($cart, true);
        }, $dms_new_unique);

        // Format urls such that the newest cart of each type does not have the year appended
        $dms_new_unique = array_map(function($cart) use (&$dms_new_unique){
            $should_have_year = false;
            // Loop through each other cart
            if(gettype($cart) === 'array') foreach ($dms_new_unique as &$other) {
                // If it hasn't been formatted already
                if (gettype($other) === 'array') {
                    // If it's the same type
                    if ($other['url'] === $cart['url']) {
                        // If other is older, append year to other
                        if ($cart['year'] > $other['year']) {
                            $other = $other['url'] . '-' . $other['year'];
                        // If self is older, append year to self
                        } else if ($cart['year'] < $other['year']) {
                            $should_have_year = true;
                        }
                    }
                }
            }
            return $cart['url'] . ($should_have_year?('-'.$cart['year']):'');
        }, $dms_new_unique);
        
        // Carts on site which are not on DMS
        $extra_new_pids = $new_carts;
        foreach($dms_new_unique as $url) {
            $pos = array_search($url, $extra_new_pids);
            array_splice($extra_new_pids, $pos, 1);
        }
        $extra_new_pids = array_map(function($slug) {
            return get_page_by_path($slug, OBJECT, 'product')->ID;
        }, $extra_new_pids);
        // Carts on DMS which are not on site
        $missing_new_skus = array_values(array_diff($dms_new_unique, $new_carts));

    ### Used Operation ###

        // Get used cart data from DMS
        $dms_used = json_decode(DMS_Connector::request('{"isUsed":true, "isInStock": true, "isInBoneyard": false, "needOnWebsite": true}', '/chimera/lookup', 'POST'), true)??[];
        $dms_used_skus = array_map(function($cart) {
            return $cart['vinNo']?$cart['vinNo']:$cart['serialNo'];
        }, $dms_used);

        $extra_used_pids = array_values(array_diff($used_carts, $dms_used_skus));
        $extra_used_pids = array_map(function($sku) {
            return wc_get_product_id_by_sku( $sku );
        }, $extra_used_pids);
        $missing_used_skus = array_values(array_diff($dms_used_skus, $used_carts));

    ### Data Parsing ###
        
        $new_cart_pages = count($new_carts);
        $new_not_on_site = count($missing_new_skus);
        $new_not_on_dms = count($extra_new_pids);
        $new_cart_dms = count($dms_new_unique);

        $new_accurate = ($new_cart_pages - $new_not_on_dms) / ($new_cart_pages + $new_not_on_site) * 100;
        $new_extra = ($new_cart_pages / ($new_cart_pages + $new_not_on_site)) * 100;


        $used_cart_pages = count($used_carts);
        $used_not_on_site = count($missing_used_skus);
        $used_not_on_dms = count($extra_used_pids);
        $used_cart_dms = count($dms_used);

        $used_accurate = ($used_cart_pages - $used_not_on_dms) / ($used_cart_pages + $used_not_on_site) * 100;
        $used_extra = ($used_cart_pages / ($used_cart_pages + $used_not_on_site)) * 100;

    ### HTML Element Formatting ###

        $missing_new_string = '<p>Missing New: [ '.implode(', ', $missing_new_skus).' ]</p>';
        $new_links = [];
        foreach($extra_new_pids as $pid) {
            array_push($new_links,
                '<a href="' .
                site_url() . "/wp-admin/post.php?post=$pid&action=edit&classic-editor" .
                '">' . $pid . '</a>');
        }
        $extra_new_string = '<p>Extra New: [ '.implode(', ', $new_links).' ]</p>';

        $missing_used_string = '<p>Missing Used: [ '.implode(', ', $missing_used_skus).' ]</p>';
        $used_links = [];
        foreach($extra_used_pids as $pid) {
            array_push($used_links,
                '<a href="' .
                site_url() . "/wp-admin/post.php?post=$pid&action=edit&classic-editor" .
                '">' . $pid . '</a>');
        }
        $extra_used_string = '<p>Extra Used: [ '.implode(', ', $used_links).' ]</p>';

    ### Output ###

        self::page_header();

        echo '
        <div class="body" style="flex-direction: column;">
            <div class="action-box-group">
                <div class="action-box primary" id="new-status">
                    <h2>New Carts</h2>
                    <p>Pages on site: '.$new_cart_pages.'</p>
                    <p>URLs on DMS: '.$new_cart_dms.'</p>
                    <p>Missing site pages: '.$new_not_on_site.'</p>
                    <p>Extra site pages: '.$new_not_on_dms.'</p>
                </div>
                <div class="action-box secondary" id="used-status">
                    '.$missing_new_string.'
                    '.$extra_new_string.'
                </div>
                <div class="action-box primary" id="new-chart" style="flex-direction:row; gap: 2.5rem">
                    <div class="action-box-column chart-container">
                        <div class="pie-chart" style="--percent-accurate: '.$new_accurate.'%; --percent-extra: '.$new_extra.'%;">
                            New Carts
                        </div>
                    </div>
                    <div class="action-box-column chart-legend">
                        <div style="--color: var(--good)">Inventory on site and DMS</div>
                        <div style="--color: var(--warning)">Inventory not on DMS</div>
                        <div style="--color: var(--bad)">Inventory not on site</div>
                    </div>
                </div>
            </div>
            <div class="action-box-group">
                <div class="action-box primary" id="used-status">
                    <h2>Used Carts</h2>
                    <p>Pages on site: '.$used_cart_pages.'</p>
                    <p>Carts on DMS: '.$used_cart_dms.'</p>
                    <p>Missing site pages: '.$used_not_on_site.'</p>
                    <p>Extra site pages: '.$used_not_on_dms.'</p>
                </div>
                <div class="action-box secondary" id="used-status">
                    '.$missing_used_string.'
                    '.$extra_used_string.'
                </div>
                <div class="action-box primary" id="used-chart" style="flex-direction:row; gap: 2.5rem">
                    <div class="action-box-column chart-container">
                        <div class="pie-chart" style="--percent-accurate: '.$used_accurate.'%; --percent-extra: '.$used_extra.'%;">
                            Used Carts
                        </div>
                    </div>
                    <div class="action-box-column chart-legend">
                        <div style="--color: var(--good)">Inventory on site and DMS</div>
                        <div style="--color: var(--warning)">Inventory not on DMS</div>
                        <div style="--color: var(--bad)">Inventory not on site</div>
                    </div>
                </div>
            </div>
        </div>
        ';
    }

    public static function import_page()
    {
        $nonce = wp_create_nonce("tigon_dms_run_import_nonce");
        $link = admin_url('admin-ajax.php?action=tigon_dms_run_import&nonce=' . $nonce);

        self::page_header();

        echo '
        <div class="body">
            <!--<div class="action-box" style="flex-direction:row;">
                <a id="new" class="tigon_dms_action tigon_dms_import" data-nonce="' . $nonce . '" href="' . $link . '"><button>Import New Carts</button></a>
            </div>-->
            <div class="tabbed-panel">
                <div class="tigon-dms-nav" style="flex-direction:row;">
                    <button class="tigon-dms-tab active" id="used-tab">Used Carts</button>
                    <button class="tigon-dms-tab" id="new-tab">New Carts</button>
                </div>

                <div class="action-box" id="used-panel">
                    <div class="action-box-column">
                        <div id="mobile-title">Import Used Cart Data From DMS</div>
                        <h3>Import settings</h3>
                        <div>Input the VIN or Serial Number of a cart on the DMS to be imported.</div>
                        <div class="form"><form>
                            <div>
                                <span>VIN Number:</span>
                                <input type="text" list="vin" class="input-list" style="float:right" name="VIN Number" id="txt-vin" />
                                <datalist id="vin">
                                </datalist>
                            </div>
                            <div>
                                <span>Serial Number:</span>
                                <input type="text" list="serial" class="input-list" style="float:right" name="Serial Number" id="txt-serial" />
                                <datalist id="serial">
                                </datalist>
                            </div>
                            <div>
                                <span>
                                    <span>Import All Used Carts?</span>
                                    <input type="checkbox" id="chk-all-carts" />
                                </span>
                            </div>
                        </div>
                    </form></div>
                    <div class="action-box-column">
                        <h3>Import Used Cart Data from DMS</h3>
                        <div id="warning" class="warning"><i>This process may take several minutes. This should only be done in the case that a large portion of the inventory is missing or outdated.</i></div>
                        <a id="used" class="tigon_dms_action tigon_dms_import" data-nonce="' . $nonce . '" href="' . $link . '"><button>Import Selected</button></a>
                    </div>
                </div>

                <div class="action-box" id="new-panel">
                    <div class="action-box-column" style="max-width:28rem; align-self:unset">
                        <h3>Import New Cart Data from DMS</h3>
                        <div class="warning"><i>This process may take several minutes. This should only be done in the case that a large portion of the inventory is missing or outdated.</i></div>
                        <a id="new" class="tigon_dms_action tigon_dms_import" data-nonce="' . $nonce . '" href="' . $link . '"><button>Import All</button></a>
                    </div>

                    <div class="action-box-column">
                    ' . self::checkboxes() . '
                    </div>
                </div>
            </div>
            <div class="action-box" id="result-box">
                <div id="progress-bar-container"><div id="progress-bar"></div></div>
                <div id="progress-text"></div>
                <hr id="result-separator">
                <div id="result"></div>
                <div id="errors"></div>
            </div>
        </div>
        ';
    }

    public static function settings_page()
    {
        $nonce = wp_create_nonce("tigon_dms_run_import_nonce");
        $link = admin_url('admin-ajax.php?action=tigon_dms_save_settings&nonce=' . $nonce);

        $dms_url = 'dms.com';
        $api_key = 'key';
        $file_source = 'cdn.com';

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = $wpdb->prefix . 'tigon_dms_config';

        $github_token = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'github_token'") ?? 'e.g. github_pat_...';
        $github_token = substr($github_token, 0, 16) . '•••••••••••••••' . substr($github_token, -5);

        $dms_url = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'dms_url'") ?? 'e.g. https://api.your-dms.com';

        $api_key = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'user_token'") ?? 'e.g. 00000000-0000-0000-000000000000';
        $shown_key = substr($api_key, -6);
        $api_key = substr_replace(preg_replace('/[^-]/', '•', $api_key), $shown_key, -6, 6);

        $file_source = $wpdb->get_var("SELECT option_value FROM $table_name WHERE option_name = 'file_source'") ?? 'e.g. https://s3.amazonaws.com/your.bucket.s3';

        // Handle Tools tab POST submissions
        $tool_results = '';
        if (isset($_POST['dms_tool_action']) && check_admin_referer('dms_tools_nonce', 'dms_tools_nonce_field')) {
            $tool_results = self::run_tool($_POST['dms_tool_action']);
        }

        self::page_header();

        // Textbox placeholders from database
        $name = '{^make}® {^model} {cartColor} in {city}, {stateAbbr}';
        $slug = '{make}-{model}-{cartColor}-seat-{seatColor}-{city}-{state}';
        $image_name = '{^make}® {^model} {cartColor} in {city}, {stateAbbr} image';
        $monroney_name = '{^make}® {^model} {cartColor} in {city}, {stateAbbr} monroney';
        $description = 'Lorem ipsum dolor sit amet';
        $short_description = 'Lorem ipsum';

        echo '
        <div class="body">
            <div class="tabbed-panel">
                <div class="tigon-dms-nav" style="flex-direction:row;">
                    <button class="tigon-dms-tab" id="general-tab">General</button>
                    <button class="tigon-dms-tab" id="schema-tab">Schema</button>
                    <button class="tigon-dms-tab" id="tools-tab">Tools</button>
                </div>

                <div class="action-box" id="general">
                    <h3>General Configuration</h3>
                    <div class="settings form">
                        <div>
                            <span>GitHub Access Token:</span>
                            <input type="text" style="float:right" id="txt-github-token" placeholder="' . $github_token . '" />
                        </div>
                        <div>
                            <span>DMS API Address:</span>
                            <input type="text" style="float:right" id="txt-url" placeholder="' . $dms_url . '" />
                        </div>
                        <div>
                            <span>DMS Amplify User ID:</span>
                            <input type="text" style="float:right" id="txt-api-key" placeholder="' . $api_key . '"></textarea>
                        </div>
                        <div>
                            <span>File source:</span>
                            <input type="text" style="float:right" id="txt-file-source" placeholder="' . $file_source . '"></textarea>
                        </div>
                    </div>
                    <a id="save" class="tigon_dms_action tigon_dms_save" data-nonce="' . $nonce . '"><button>Save Settings</button></a>
                </div>

                <div class="action-box" id="schema">
                    <h3>Schema Setup</h3>
                    <div class="drag-n-drop">
                        <div class="settings form" id="attr-form">
                            <div>
                                <span>Name Schema:</span>
                                <input type="text" style="float:right" id="Name Schema" placeholder="'.$name.'" />
                            </div>
                            
                            <div>
                                <span>Slug Schema:</span>
                                <input type="text" style="float:right" id="Slug Schema" placeholder="'.$slug.'" />
                            </div>
                            
                            <div>
                                <span>Image Name:</span>
                                <input type="text" style="float:right" id="Image Name" placeholder="'.$image_name.'" />
                            </div>
                            
                            <div>
                                <span>Monroney Name:</span>
                                <input type="text" style="float:right" id="Monroney Name" placeholder="'.$monroney_name.'" />
                            </div>
                            
                            <div>
                                <span>Description:</span>
                                <input type="text" style="float:right" id="Description" placeholder="'.$description.'" />
                            </div>
                            
                            <div>
                                <span>Short Description:</span>
                                <input type="text" style="float:right" id="Short Description" placeholder="'.$short_description.'" />
                            </div>
                            
                            <!--<div>
                                <span>Field Overrides:</span>
                                <input type="text" style="float:right" id="Field Overrides" placeholder="'.$dms_url.'" />
                            </div>-->

                            <div>
                                <span class="caret">Catgories:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Attributes:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Taxonomies:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div>
                                <span class="caret">Product Addons:</span>
                            </div>
                            
                            <div style="flex-direction:column;">
                                <span class="caret" style="align-self:flex-start">Custom Tabs:</span>
                                <span class="nested">
                                    <div>
                                        <span>Short Description:</span>
                                        <input type="text" style="float:right" id="Short Description" placeholder="'.$dms_url.'" />
                                    </div>
                                    <div>
                                        <span>Short Description:</span>
                                        <input type="text" style="float:right" id="Short Description" placeholder="'.$dms_url.'" />
                                    </div>
                                </span>
                            </div>
                        </div>
                        <div id="dms-schema">
                            loading dms properties...
                        </div>
                    </div>
                    <a id="save" class="tigon_dms_action tigon_dms_schema_save" data-nonce="' . $nonce . '"><button>Save Settings</button></a>
                </div>

                <div class="action-box" id="tools" style="flex-direction:column;">
                    <h3>Maintenance Tools</h3>
                    <p style="margin-bottom:1.5rem;color:#666;">One-time recovery and migration utilities for DMS products.</p>
                    ' . $tool_results . '
                    <div style="display:flex;flex-direction:column;gap:1.5rem;">
                        <div class="settings form" style="padding:1rem;border:1px solid #ddd;border-radius:4px;">
                            <h4 style="margin:0 0 0.5rem;">Recover Products</h4>
                            <p style="margin:0 0 0.75rem;color:#666;font-size:0.9em;">Removes the <code>exclude-from-search</code> term from DMS products so they appear in WP Admin product lists.</p>
                            <form method="post" action="">
                                ' . wp_nonce_field('dms_tools_nonce', 'dms_tools_nonce_field', true, false) . '
                                <input type="hidden" name="dms_tool_action" value="recover_products" />
                                <button type="submit" class="button">Run Recovery</button>
                            </form>
                        </div>
                        <div class="settings form" style="padding:1rem;border:1px solid #ddd;border-radius:4px;">
                            <h4 style="margin:0 0 0.5rem;">Normalize Titles</h4>
                            <p style="margin:0 0 0.75rem;color:#666;font-size:0.9em;">Normalizes DMS product titles to use " In " format instead of dashes or en-dashes, and updates slugs.</p>
                            <form method="post" action="">
                                ' . wp_nonce_field('dms_tools_nonce', 'dms_tools_nonce_field', true, false) . '
                                <input type="hidden" name="dms_tool_action" value="normalize_titles" />
                                <button type="submit" class="button">Run Normalization</button>
                            </form>
                        </div>
                        <div class="settings form" style="padding:1rem;border:1px solid #ddd;border-radius:4px;">
                            <h4 style="margin:0 0 0.5rem;">Update Titles with &reg;</h4>
                            <p style="margin:0 0 0.75rem;color:#666;font-size:0.9em;">Rebuilds DMS product titles from the DMS payload to include the &reg; symbol between make and model.</p>
                            <form method="post" action="">
                                ' . wp_nonce_field('dms_tools_nonce', 'dms_tools_nonce_field', true, false) . '
                                <input type="hidden" name="dms_tool_action" value="update_titles_reg" />
                                <button type="submit" class="button">Run Title Update</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
                </div>
        </div>
        ';
    }

    /**
     * Run a maintenance tool and return HTML results.
     */
    private static function run_tool(string $action): string
    {
        if (!current_user_can('manage_options')) {
            return '<div class="notice notice-error"><p>Unauthorized.</p></div>';
        }

        switch ($action) {
            case 'recover_products':
                return self::tool_recover_products();
            case 'normalize_titles':
                return self::tool_normalize_titles();
            case 'update_titles_reg':
                return self::tool_update_titles_reg();
            default:
                return '';
        }
    }

    private static function tool_recover_products(): string
    {
        $fixed = [];
        $exclude_term = get_term_by('slug', 'exclude-from-search', 'product_visibility');
        if (!$exclude_term) {
            return '<div class="notice notice-info" style="margin:1rem 0;"><p>Term "exclude-from-search" not found. Nothing to fix.</p></div>';
        }

        $product_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [['taxonomy' => 'product_visibility', 'field' => 'slug', 'terms' => 'exclude-from-search']],
        ]);

        foreach ($product_ids as $pid) {
            $terms = wp_get_object_terms($pid, 'product_visibility', ['fields' => 'slugs']);
            $new_terms = array_values(array_filter($terms, fn($t) => $t !== 'exclude-from-search'));
            if (count($new_terms) !== count($terms)) {
                wp_set_object_terms($pid, $new_terms, 'product_visibility');
                $fixed[] = ['id' => $pid, 'title' => get_the_title($pid), 'kept' => implode(', ', $new_terms) ?: '(none)'];
            }
        }

        if (empty($fixed)) {
            return '<div class="notice notice-info" style="margin:1rem 0;"><p>No products needed fixing.</p></div>';
        }

        $html = '<div class="notice notice-success" style="margin:1rem 0;"><p>Fixed ' . count($fixed) . ' product(s). Removed <code>exclude-from-search</code> term.</p></div>';
        $html .= '<table class="widefat striped" style="max-width:800px;margin:1rem 0;"><thead><tr><th>ID</th><th>Product</th><th>Remaining Terms</th></tr></thead><tbody>';
        foreach ($fixed as $item) {
            $html .= '<tr><td><a href="' . get_edit_post_link($item['id']) . '">' . $item['id'] . '</a></td>';
            $html .= '<td>' . esc_html($item['title']) . '</td>';
            $html .= '<td><code>' . esc_html($item['kept']) . '</code></td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private static function tool_normalize_titles(): string
    {
        $fixed = [];
        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => [['key' => '_dms_cart_id', 'compare' => 'EXISTS']],
        ]);

        foreach ($products as $product) {
            $original_title = $product->post_title;
            $original_slug  = $product->post_name;
            $normalized_title = tigon_dms_normalize_title($original_title);
            $normalized_slug  = sanitize_title($normalized_title);

            if ($original_title === $normalized_title && $original_slug === $normalized_slug) {
                continue;
            }

            wp_update_post(['ID' => $product->ID, 'post_title' => $normalized_title, 'post_name' => $normalized_slug]);
            $fixed[] = ['id' => $product->ID, 'old' => $original_title, 'new' => $normalized_title, 'slug' => $normalized_slug];
        }

        if (empty($fixed)) {
            return '<div class="notice notice-info" style="margin:1rem 0;"><p>No products needed normalizing.</p></div>';
        }

        $html = '<div class="notice notice-success" style="margin:1rem 0;"><p>Normalized ' . count($fixed) . ' product(s) to use " In " format.</p></div>';
        $html .= '<table class="widefat striped" style="max-width:1000px;margin:1rem 0;"><thead><tr><th>ID</th><th>Old Title</th><th>New Title</th><th>Slug</th></tr></thead><tbody>';
        foreach ($fixed as $item) {
            $html .= '<tr><td><a href="' . get_edit_post_link($item['id']) . '">' . $item['id'] . '</a></td>';
            $html .= '<td>' . esc_html($item['old']) . '</td>';
            $html .= '<td><strong>' . esc_html($item['new']) . '</strong></td>';
            $html .= '<td><code>' . esc_html($item['slug']) . '</code></td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private static function tool_update_titles_reg(): string
    {
        $fixed = [];
        $errors = [];
        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => [['key' => '_dms_cart_id', 'compare' => 'EXISTS']],
        ]);

        foreach ($products as $product) {
            $payload_json = get_post_meta($product->ID, '_dms_payload', true);
            if (empty($payload_json)) {
                $errors[] = 'Product #' . $product->ID . ' has no DMS payload';
                continue;
            }
            $cart_data = json_decode($payload_json, true);
            if (empty($cart_data) || !is_array($cart_data)) {
                $errors[] = 'Product #' . $product->ID . ' has invalid DMS payload';
                continue;
            }

            $make = $cart_data['cartType']['make'] ?? '';
            $model = $cart_data['cartType']['model'] ?? '';
            $color = $cart_data['cartAttributes']['cartColor'] ?? '';
            $store_id = $cart_data['cartLocation']['locationId'] ?? '';

            $location_string = '';
            if (!empty($store_id) && class_exists('DMS_API')) {
                $loc = \DMS_API::get_city_and_state_by_store_id($store_id);
                $location_string = trim(($loc['city'] ?? '') . ', ' . ($loc['state'] ?? ''), ', ');
            }

            $parts = [];
            if (!empty($make) && !empty($model)) {
                $parts[] = $make . "\u{00AE} " . $model;
            } elseif (!empty($make)) {
                $parts[] = $make;
            } elseif (!empty($model)) {
                $parts[] = $model;
            }
            if (!empty($color)) {
                $parts[] = $color;
            }
            $new_title = trim(implode(' ', $parts));
            if (!empty($location_string)) {
                $new_title .= ' In ' . $location_string;
            }
            $new_title = tigon_dms_normalize_title($new_title);
            $new_slug  = sanitize_title($new_title);

            if ($product->post_title === $new_title) {
                continue;
            }

            $result = wp_update_post(['ID' => $product->ID, 'post_title' => $new_title, 'post_name' => $new_slug]);
            if (is_wp_error($result)) {
                $errors[] = 'Product #' . $product->ID . ': ' . $result->get_error_message();
                continue;
            }
            $fixed[] = ['id' => $product->ID, 'old' => $product->post_title, 'new' => $new_title, 'slug' => $new_slug];
        }

        $html = '';
        if (!empty($errors)) {
            $html .= '<div class="notice notice-error" style="margin:1rem 0;"><p><strong>Errors:</strong><ul>';
            foreach ($errors as $e) {
                $html .= '<li>' . esc_html($e) . '</li>';
            }
            $html .= '</ul></p></div>';
        }
        if (empty($fixed)) {
            $html .= '<div class="notice notice-info" style="margin:1rem 0;"><p>No products needed title updates.</p></div>';
            return $html;
        }
        $html .= '<div class="notice notice-success" style="margin:1rem 0;"><p>Updated ' . count($fixed) . ' product(s) with &reg; symbol.</p></div>';
        $html .= '<table class="widefat striped" style="max-width:1000px;margin:1rem 0;"><thead><tr><th>ID</th><th>Old Title</th><th>New Title</th><th>Slug</th></tr></thead><tbody>';
        foreach ($fixed as $item) {
            $html .= '<tr><td><a href="' . get_edit_post_link($item['id']) . '">' . $item['id'] . '</a></td>';
            $html .= '<td>' . esc_html($item['old']) . '</td>';
            $html .= '<td><strong>' . esc_html($item['new']) . '</strong></td>';
            $html .= '<td><code>' . esc_html($item['slug']) . '</code></td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private static function checkboxes(): string
    {
        $product_fields = Product_Fields::get_options();
        $component = '
            <div class="import-fields-head">
                <div class="cb-container top-level" style="margin-right:-3.4rem; flex-grow:0;">
                    <input type="checkbox"/>
                    All
                </div>
                <h2 style="margin:auto;">Force Overwrite Data</h2>
                <span class="caret" style="margin-left:-1rem"></span>
            </div>
            <div class="checkbox-list hidden">
        ';
        foreach ($product_fields as $field) {
            $component = $component .
                '<div class="cb-container import-field" constant="' .
                strtoupper($field) .
                '"><input type="checkbox" /> ' .
                ucwords(str_replace('_', ' ', $field)) .
                '</div>';
        }
        $component = $component . '</div>';

        return $component;
    }
}
