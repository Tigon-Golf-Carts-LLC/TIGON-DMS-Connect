<?php
/**
 * Plugin Name: TIGON DMS Connect
 * Plugin URI: https://tigongolfcarts.com/
 * Description: Tigon DMS Connect fetches, imports, maps, and displays golf carts from the DMS into WooCommerce.
 * Version: 2.0.2
 * Author: Tigon Golf Carts
 * Author URI: https://tigongolfcarts.com/
 * Text Domain: tigon-dms-connect
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('TIGON_DMS_MAIN_FILE')) {
    define('TIGON_DMS_MAIN_FILE', __FILE__);
}

require_once __DIR__ . '/dms-bridge-plugin.php';
