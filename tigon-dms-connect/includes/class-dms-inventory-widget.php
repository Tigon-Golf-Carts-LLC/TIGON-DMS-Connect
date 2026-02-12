<?php

/**
 * Elementor Widget for DMS Inventory (with Filters & Pagination)
 *
 * This is a SEPARATE widget from DMS_Elementor_Widget (dms_carts).
 * Used on /inventory page to show all carts with filters and pagination.
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class DMS_Inventory_Widget extends \Elementor\Widget_Base
{

    /**
     * Get widget name
     */
    public function get_name()
    {
        return 'dms_inventory_filtered';
    }

    /**
     * Get widget title
     */
    public function get_title()
    {
        return __('DMS Inventory (Filtered)', 'tigon-dms-connect');
    }

    /**
     * Get widget icon
     */
    public function get_icon()
    {
        return 'eicon-products';
    }

    /**
     * Get widget categories
     */
    public function get_categories()
    {
        return ['general'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords()
    {
        return ['dms', 'inventory', 'carts', 'filter', 'golf'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls()
    {

        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Inventory Settings', 'tigon-dms-connect'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_filters',
            [
                'label' => __('Show Filters Sidebar', 'tigon-dms-connect'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'tigon-dms-connect'),
                'label_off' => __('No', 'tigon-dms-connect'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_pagination',
            [
                'label' => __('Show Pagination', 'tigon-dms-connect'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'tigon-dms-connect'),
                'label_off' => __('No', 'tigon-dms-connect'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => __('Carts Per Page', 'tigon-dms-connect'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 4,
                'max' => 40,
                'step' => 4,
                'default' => 20,
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend and in editor
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $show_filters = $settings['show_filters'] === 'yes';
        $show_pagination = $settings['show_pagination'] === 'yes';
        $per_page = intval($settings['per_page'] ?? 20);

        // Render the inventory container
        self::render_inventory_container($show_filters, $show_pagination, $per_page);
    }

    /**
     * Render inventory container (shared by widget and shortcode)
     *
     * @param bool $show_filters    Whether to show filters sidebar
     * @param bool $show_pagination Whether to show pagination
     * @param int  $per_page        Number of carts per page
     */
    public static function render_inventory_container($show_filters = true, $show_pagination = true, $per_page = 20)
    {
        // Enqueue required assets
        self::enqueue_inventory_assets();

        $container_class = 'dms-inventory-container';
        if (!$show_filters) {
            $container_class .= ' dms-inventory-no-sidebar';
        }
?>
        <div class="<?php echo esc_attr($container_class); ?>"
            id="dms-inventory-root"
            data-show-filters="<?php echo $show_filters ? 'true' : 'false'; ?>"
            data-show-pagination="<?php echo $show_pagination ? 'true' : 'false'; ?>"
            data-per-page="<?php echo esc_attr($per_page); ?>">

            <?php if ($show_filters): ?>
                <!-- Filters Sidebar -->
                <aside class="dms-filters-sidebar" id="dms-filters-sidebar">
                    <div class="dms-filters-header" id="dms-filters-toggle">
                        <span class="dms-filters-icon">☰</span>
                        <h3>SEARCH FILTERS</h3>
                        <span class="dms-filters-toggle-arrow">▼</span>
                    </div>

                    <div class="dms-filters-content" id="dms-filters-content">
                
                <!-- Search Bar -->
                <div class="dms-search-bar">
                    <input type="text" 
                           id="dms-search-input" 
                           class="dms-search-input" 
                           placeholder="Search carts...">
                    <button type="button" id="dms-search-btn" class="dms-search-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- 1. Basic Filters Section -->
                <!-- <div class="dms-filter-section-heading">Basic Filters</div> -->
                
                <!-- Condition Filter -->
                <div class="dms-filter-group" data-filter="condition">
                    <h4 class="dms-filter-title">Condition</h4>
                    <div class="dms-filter-options">
                        <label><input type="checkbox" name="condition" value="new" data-key="isNew"> New <span class="dms-filter-count" data-count="new_count"></span></label>
                        <label><input type="checkbox" name="condition" value="used" data-key="isUsed"> Used <span class="dms-filter-count" data-count="used_count"></span></label>
                    </div>
                </div>

                <!-- Fuel Type Filter (renamed from Power Source) -->
                <div class="dms-filter-group" data-filter="fuelType">
                    <h4 class="dms-filter-title">Fuel Type</h4>
                    <div class="dms-filter-options">
                        <label><input type="checkbox" value="electric" data-key="isElectric"> Electric <span class="dms-filter-count" data-count="electric_count"></span></label>
                        <label><input type="checkbox" value="gas" data-key="isGas"> Gas <span class="dms-filter-count" data-count="gas_count"></span></label>
                    </div>
                </div>

                <!-- Street Legal Filter -->
                <div class="dms-filter-group" data-filter="streetLegal">
                    <h4 class="dms-filter-title">Street Legal</h4>
                    <div class="dms-filter-options">
                        <label><input type="checkbox" name="streetLegal" value="yes" data-key="isStreetLegal"> Yes <span class="dms-filter-count" data-count="street_legal_yes_count"></span></label>
                        <label><input type="checkbox" name="streetLegal" value="no" data-key="isNotStreetLegal"> No <span class="dms-filter-count" data-count="street_legal_no_count"></span></label>
                    </div>
                </div>

                <!-- Lifted Filter -->
                <div class="dms-filter-group" data-filter="lifted">
                    <h4 class="dms-filter-title">Lifted</h4>
                    <div class="dms-filter-options">
                        <label><input type="checkbox" name="lifted" value="yes" data-key="isLifted"> Yes <span class="dms-filter-count" data-count="lifted_yes_count"></span></label>
                        <label><input type="checkbox" name="lifted" value="no" data-key="isNotLifted"> No <span class="dms-filter-count" data-count="lifted_no_count"></span></label>
                    </div>
                </div>

                <!-- 2. Location Filter (dynamically populated from /tigon-stores API) -->
                <div class="dms-filter-group" data-filter="locations">
                    <h4 class="dms-filter-title">Location</h4>
                    <div class="dms-filter-options" id="dms-filter-locations-options">
                        <!-- Populated by JavaScript from API - format: storeId + city -->
                    </div>
                </div>

                <!-- 3. Seats Filter -->
                <div class="dms-filter-group" data-filter="seats">
                    <h4 class="dms-filter-title">Seats</h4>
                    <div class="dms-filter-options">
                        <label><input type="checkbox" value="Utility"> Utility <span class="dms-filter-count" data-count="utility_count"></span></label>
                        <label><input type="checkbox" value="2 Passenger"> 2 Seats <span class="dms-filter-count" data-count="2_seat_count"></span></label>
                        <label><input type="checkbox" value="4 Passenger"> 4 Seats <span class="dms-filter-count" data-count="4_seat_count"></span></label>
                        <label><input type="checkbox" value="6 Passenger"> 6 Seats <span class="dms-filter-count" data-count="6_seat_count"></span></label>
                        <label><input type="checkbox" value="8 Passenger"> 8 Seats <span class="dms-filter-count" data-count="8_seat_count"></span></label>
                    </div>
                </div>

                <!-- 4. Battery Type Filter (shown only when Electric is selected) -->
                <div class="dms-filter-group" data-filter="batteryType" id="dms-battery-type-filter" style="display: none;">
                    <h4 class="dms-filter-title">Battery Type</h4>
                    <div class="dms-filter-options">
                        <label><input type="checkbox" value="Lithium"> Lithium</label>
                        <label><input type="checkbox" value="Lead"> Lead</label>
                        <label><input type="checkbox" value="AGM"> AGM</label>
                        <label><input type="checkbox" value="36V"> 36V</label>
                        <label><input type="checkbox" value="48V"> 48V</label>
                        <label><input type="checkbox" value="72V"> 72V</label>
                    </div>
                </div>

                <!-- 5. Make Filter (dynamically populated) -->
                <div class="dms-filter-group" data-filter="makes">
                    <h4 class="dms-filter-title">Make</h4>
                    <div class="dms-filter-options" id="dms-filter-makes-options">
                        <!-- Populated by JavaScript from makesData -->
                    </div>
                </div>

                <!-- 6. Models Filter (collapsible, initially hidden, dynamically populated) -->
                <div class="dms-filter-group dms-filter-collapsed" data-filter="models">
                    <h4 class="dms-filter-title dms-filter-toggle">Model <span class="dms-toggle-icon">+</span></h4>
                    <div class="dms-filter-options dms-filter-hidden" id="dms-filter-models-options">
                        <!-- Models will be populated based on selected makes -->
                        <p class="dms-filter-hint">Select a make first</p>
                    </div>
                </div>

                <!-- 7. Colors Filter (dynamically populated based on selected makes) -->
                <div class="dms-filter-group dms-filter-collapsed" data-filter="colors">
                    <h4 class="dms-filter-title dms-filter-toggle">Colors <span class="dms-toggle-icon">+</span></h4>
                    <div class="dms-filter-options dms-filter-hidden" id="dms-filter-colors-options">
                        <p class="dms-filter-hint">Select a make first</p>
                    </div>
                </div>

                <!-- Reset Filters Button -->
                <button type="button" class="dms-reset-filters-btn" id="dms-reset-filters">
                    Reset Filters
                </button>
                </div><!-- /.dms-filters-content -->
                </aside>
            <?php endif; ?>

            <!-- Main Content Area -->
            <main class="dms-inventory-main">
                <!-- Header -->
                <div class="dms-inventory-header">
                    <h2 class="dms-inventory-title">SHOWING ALL INVENTORY</h2>
                    <select class="dms-sort-dropdown" id="dms-sort-dropdown">
                        <option value="">Default sorting</option>
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                    </select>
                </div>

                <!-- Loading Indicator -->
                <div class="dms-inventory-loading" id="dms-inventory-loading" style="display: none;">
                    <div class="dms-loading-spinner"></div>
                    <p>Loading carts...</p>
                </div>

                <!-- Cart Grid -->
                <div class="dms-inventory-grid" id="dms-inventory-grid">
                    <!-- Carts will be rendered here by JavaScript -->
                </div>

                <!-- No Results Message -->
                <div class="dms-inventory-no-results" id="dms-inventory-no-results" style="display: none;">
                    <p>No carts found matching your criteria. Try adjusting your filters.</p>
                </div>

                <?php if ($show_pagination): ?>
                    <!-- Pagination -->
                    <div class="dms-inventory-pagination" id="dms-inventory-pagination">
                        <!-- Pagination will be rendered here by JavaScript -->
                    </div>
                <?php endif; ?>
            </main>
        </div>
<?php
    }

    /**
     * Enqueue inventory-specific assets
     */
    public static function enqueue_inventory_assets()
    {
        $plugin_url = defined('TIGON_DMS_PLUGIN_URL') ? TIGON_DMS_PLUGIN_URL : plugin_dir_url(dirname(__FILE__));
        $plugin_dir = defined('TIGON_DMS_PLUGIN_DIR') ? TIGON_DMS_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));

        // Enqueue Font Awesome
        wp_enqueue_style(
            'dms-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );

        // Enqueue inventory-specific CSS
        $css_file = $plugin_dir . 'assets/css/dms-inventory-filtered.css';
        wp_enqueue_style(
            'dms-inventory-filtered-css',
            $plugin_url . 'assets/css/dms-inventory-filtered.css',
            array('dms-font-awesome'),
            file_exists($css_file) ? filemtime($css_file) : TIGON_DMS_VERSION
        );

        // Enqueue API service JavaScript (dependency for inventory JS)
        $api_service_file = $plugin_dir . 'assets/js/dms-api-service.js';
        wp_enqueue_script(
            'dms-api-service-js',
            $plugin_url . 'assets/js/dms-api-service.js',
            array('jquery'),
            file_exists($api_service_file) ? filemtime($api_service_file) : TIGON_DMS_VERSION,
            true
        );

        // Enqueue inventory JavaScript
        $js_file = $plugin_dir . 'assets/js/dms-inventory-filtered.js';
        wp_enqueue_script(
            'dms-inventory-filtered-js',
            $plugin_url . 'assets/js/dms-inventory-filtered.js',
            array('jquery', 'dms-api-service-js'),
            file_exists($js_file) ? filemtime($js_file) : TIGON_DMS_VERSION,
            true
        );

        // Localize script with settings (API URLs and S3 URLs are now in dms-api-service.js)
        wp_localize_script(
            'dms-inventory-filtered-js',
            'dmsInventoryConfig',
            array(
                'baseUrl'     => home_url(),
                'pageSize'    => 20,
                'comingSoonImage' => 'https://tigongolfcarts.com/wp-content/uploads/2024/11/TIGON-GOLF-CARTS-IMAGES-COMING-SOON.jpg',
                'makesData'   => self::get_makes_data(),
            )
        );
    }

    /**
     * Render widget in editor - use server-side rendering for preview
     */
    protected function _print_content()
    {
        $this->render();
    }

    /**
     * Get makes/manufacturers data
     * 
     * @return array
     */
    private static function get_makes_data() {
        return array(
            array('label' => 'Club Car', 'key' => 'club_car', 'recordOrder' => 8),
            array('label' => 'Yamaha', 'key' => 'yamaha', 'recordOrder' => 11),
            array('label' => 'Evolution', 'key' => 'evolution', 'recordOrder' => 3),
            array('label' => 'Swift EV', 'key' => 'swift_ev', 'recordOrder' => 5),
            array('label' => 'EZGO', 'key' => 'ezgo', 'recordOrder' => 9),
            array('label' => 'Royal EV', 'key' => 'royal_ev', 'recordOrder' => 10),
            array('label' => 'Denago', 'key' => 'denago', 'recordOrder' => 1),
            array('label' => 'Icon', 'key' => 'icon', 'recordOrder' => 4),
            array('label' => 'Epic', 'key' => 'epic', 'recordOrder' => 2),
            array('label' => 'Pure EV', 'key' => 'pure_ev', 'recordOrder' => 12),
            array('label' => 'Cushman', 'key' => 'cushman', 'recordOrder' => 13),
            array('label' => 'Bintelli', 'key' => 'bintelli', 'recordOrder' => 14),
            array('label' => 'Tomberlin', 'key' => 'tomberlin', 'recordOrder' => 15),
            array('label' => 'Kandi', 'key' => 'kandi', 'recordOrder' => 16),
            array('label' => 'Vivid EV', 'key' => 'vivid_ev', 'recordOrder' => 17),
            array('label' => 'Star EV', 'key' => 'star_ev', 'recordOrder' => 18),
            array('label' => 'Mammoth EV', 'key' => 'mammoth_ev', 'recordOrder' => 19),
            array('label' => 'Venom EV', 'key' => 'venom_ev', 'recordOrder' => 20),
            array('label' => 'Trojan EV', 'key' => 'trojan_ev', 'recordOrder' => 21),
            array('label' => 'Landmaster', 'key' => 'landmaster', 'recordOrder' => 22),
            array('label' => 'Moto EV', 'key' => 'moto_ev', 'recordOrder' => 23),
            array('label' => 'Viking EV', 'key' => 'viking_ev', 'recordOrder' => 24),
            array('label' => 'Madjax', 'key' => 'madjax', 'recordOrder' => 25),
            array('label' => 'Toro', 'key' => 'toro', 'recordOrder' => 26),
            array('label' => 'Kawasaki', 'key' => 'kawasaki', 'recordOrder' => 27),
            array('label' => 'American Custom Golf Carts', 'key' => 'american_custom_golf_carts', 'recordOrder' => 28),
            array('label' => 'TEKO EV', 'key' => 'teko_ev', 'recordOrder' => 7),
            array('label' => 'Tara', 'key' => 'tara', 'recordOrder' => 6),
        );
    }
}
