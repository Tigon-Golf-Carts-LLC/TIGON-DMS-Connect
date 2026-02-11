<?php
/**
 * Elementor DMS Carts Widget
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementor Widget for DMS Carts
 */
class DMS_Bridge_Elementor_Widget extends \Elementor\Widget_Base {

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
        return __('DMS Golf Carts', 'dms-bridge');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
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
                'label' => __('Cart Settings', 'dms-bridge'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'cart_type',
            [
                'label' => __('Cart Type', 'dms-bridge'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => __('All Carts', 'dms-bridge'),
                    'new' => __('New Carts Only', 'dms-bridge'),
                    'used' => __('Used Carts Only', 'dms-bridge'),
                    'popular' => __('Popular Carts Only', 'dms-bridge'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        if (!class_exists('DMS_Display')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $type = $settings['cart_type'] ?? 'all';

        DMS_Display::render_carts($type);
    }
}

