<?php

if (!function_exists('add_action')) {
    return;
}

/**
 * Registration and load of translations.
 *
 * @see https://developer.wordpress.org/reference/functions/load_theme_textdomain/
 */
\add_action('init', function () {
    \load_theme_textdomain('itgalaxy-plugin-common-admin-generator', __DIR__ . '/languages');
});

if (!defined('ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_DIR')) {
    define('ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_DIR', __DIR__);
}

if (!defined('ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_URL')) {
    define('ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_URL', plugin_dir_url(__FILE__));
}
