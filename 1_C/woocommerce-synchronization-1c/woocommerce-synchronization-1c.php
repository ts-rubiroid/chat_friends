<?php
/**
 * WooCommerce - 1C - Data Exchange.
 *
 * @author itgalaxycompany
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce - 1C - Data Exchange
 * Plugin URI: https://plugins.itgalaxy.company/product/woocommerce-1c-data-exchange-woocommerce-1c-obmen-dannymi/
 * Description: Data exchange with 1C according to the protocol developed for 1C Bitrix. Import of the nomenclature, prices and stocks, unloading orders in 1C.
 * Version: 1.125.0
 * Author: itgalaxycompany
 * Author URI: https://itgalaxy.company
 * License: GPLv3
 * Tested up to: 6.3
 * WC tested up to: 8.1
 * Text Domain: itgalaxy-woocommerce-1c
 * Domain Path: /languages/
 */

use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
}
update_site_option('wc-itgalaxy-1c-exchange-purchase-code', '***************');
/**
 * Use composer autoloader.
 */
require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * Registration and load of translations.
 *
 * @see https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
 */
\add_action('init', function () {
    \load_plugin_textdomain('itgalaxy-woocommerce-1c', false, dirname(\plugin_basename(__FILE__)) . '/languages');
});

/**
 * Register plugin uninstall hook.
 *
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
\register_uninstall_hook(__FILE__, [Bootstrap::class, 'pluginUninstall']);

/**
 * Load plugin.
 */
Bootstrap::getInstance(__FILE__);
