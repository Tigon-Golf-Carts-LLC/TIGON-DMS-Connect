<?php
/**
 * Custom Homepage & Location Template for DMS Carts
 *
 * Displays only cart listings for:
 * - Homepage (national)
 * - Location pages (/hatfield, /ocean_view, etc)
 *
 * @package DMS_Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>

    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .dms-page-wrapper {
            width: 100%;
            min-height: 100vh;
            background: #f5f5f5;
            padding: 40px 20px;
        }
        .dms-carts-container {
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>

<body <?php body_class('dms-carts-page'); ?>>

<div class="dms-page-wrapper">
    <div class="dms-carts-container">

        <?php
        /**
         * IMPORTANT:
         * Key resolution happens inside DMS_Display
         * - homepage  → national
         * - /hatfield → tigon_hatfield
         */
        if (class_exists('DMS_Display')) {
            DMS_Display::render_carts();
        }
        ?>

    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
