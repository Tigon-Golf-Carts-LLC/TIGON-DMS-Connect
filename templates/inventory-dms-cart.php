<?php
/**
 * Template for DMS Cart on Inventory Page
 * This template is used when /inventory/slug matches a DMS cart
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

$cart_id = get_query_var('dms_cart_id');
$is_dms_cart = get_query_var('is_dms_cart');

if (!$is_dms_cart || empty($cart_id)) {
    // Fallback to regular template
    get_header();
    the_content();
    get_footer();
    exit;
}

get_header();
?>

<div class="dms-inventory-page-wrapper">
    <?php
    // Use existing single cart display function
    if (class_exists('DMS_Display')) {
        DMS_Display::render_single_cart($cart_id);
    }
    ?>
</div>

<?php
get_footer();
?>