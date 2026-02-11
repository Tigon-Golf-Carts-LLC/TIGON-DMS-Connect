<?php
/**
 * Single Cart Detail Page Template
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get cart slug from query var
$cart_slug = get_query_var('dms_cart_slug');

if (empty($cart_slug)) {
    // Should not happen if rewrite rules are working
    status_header(404);
    get_header();
    echo '<div class="dms-cart-not-found"><h1>Cart Not Found</h1><p>The requested cart could not be found.</p></div>';
    get_footer();
    exit;
}

// Resolve cartId from slug
$cart_id = DMS_Display::get_cart_id_from_slug($cart_slug);

if (empty($cart_id)) {
    // 404 - cart not found
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_header();
    echo '<div class="dms-cart-not-found"><h1>Cart Not Found</h1><p>The requested cart could not be found.</p></div>';
    get_footer();
    exit;
}

get_header();
?>
<div class="dms-single-cart-wrapper">
    <?php DMS_Display::render_single_cart($cart_id); ?>
</div>
<?php
get_footer();
