<?php
/**
 * Elementor Widget for DMS Carts
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

class DMS_Elementor_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'dms_carts';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('DMS Carts', 'tigon-dms-connect');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['general'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {

        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Cart Settings', 'tigon-dms-connect'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'cart_type',
            [
                'label' => __('Cart Type', 'tigon-dms-connect'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => __('All Carts (New + Used + Popular)', 'tigon-dms-connect'),
                    'new' => __('New Carts Only', 'tigon-dms-connect'),
                    'used' => __('Used Carts Only', 'tigon-dms-connect'),
                ],
                'description' => __('Popular carts only show on homepage.', 'tigon-dms-connect'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend and in editor
     */
    protected function render() {
        // Enqueue Font Awesome
        wp_enqueue_style(
            'dms-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );
        
        // Ensure CSS is loaded (important for Elementor editor preview)
        $plugin_url = defined('TIGON_DMS_PLUGIN_URL') ? TIGON_DMS_PLUGIN_URL : plugin_dir_url(dirname(__FILE__));
        $plugin_dir = defined('TIGON_DMS_PLUGIN_DIR') ? TIGON_DMS_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
        $css_file_path = $plugin_dir . 'assets/css/dms-bridge.css';
        $version = file_exists($css_file_path) ? filemtime($css_file_path) : '1.0.1'; // Auto cache-busting
        
        $dependencies = array('dms-font-awesome');
        if (defined('ELEMENTOR_VERSION')) {
            $dependencies[] = 'elementor-frontend';
        }
        
        wp_enqueue_style(
            'tigon-dms-connect-style',
            $plugin_url . 'assets/css/dms-bridge.css',
            $dependencies,
            $version
        );

        $settings = $this->get_settings_for_display();
        $cart_type = $settings['cart_type'] ?? 'all';

        if (class_exists('DMS_Display')) {
            DMS_Display::render_carts($cart_type);
        }
    }

    /**
     * Render widget in editor - use server-side rendering for real data
     */
    protected function _print_content() {
        $this->render();
    }

}

