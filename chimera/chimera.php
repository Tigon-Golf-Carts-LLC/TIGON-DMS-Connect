<?php
/*
 * Plugin Name: Chimera
 * Description: The Tigon DMS Connection Plugin
 * Version: 1.4.0
 * Author: Tigon Golf Carts
 * Author URI: https://tigongolfcarts.com/
 * Copyright: 2025 Tigon Golf Carts
 * Requires Plugins: woocommerce, elementor, pdf-embedder
 */
// Plugin by Joseph Shapiro

/*
 * Callbacks
 */

if (!defined('ABSPATH'))
    exit;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Core.php';

Tigon\Chimera\Core::init();
