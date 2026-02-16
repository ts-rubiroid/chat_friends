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
    \load_theme_textdomain('itgalaxy-plugin-common', __DIR__ . '/languages');
});
