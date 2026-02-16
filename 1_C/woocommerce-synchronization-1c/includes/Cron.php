<?php

namespace Itgalaxy\Wc\Exchange1c\Includes;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\DataDeletingOnFullExchange;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;

class Cron
{
    private static $instance = false;

    private function __construct()
    {
        add_action('init', [$this, 'createCron']);

        // not bind if run not cron mode
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }

        add_action(Bootstrap::CRON, [$this, 'cronAction']);
        add_action('termsRecount1cSynchronization', [$this, 'actionTermRecount']);
        add_action('disableItems1cSynchronization', [$this, 'actionDisableItems']);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function createCron()
    {
        if (!wp_next_scheduled(Bootstrap::CRON)) {
            wp_schedule_event(time(), 'weekly', Bootstrap::CRON);
        }
    }

    public function createCronTermRecount()
    {
        // https://developer.wordpress.org/reference/functions/wp_next_scheduled/
        if (!wp_next_scheduled('termsRecount1cSynchronization')) {
            Logger::log('termsRecount1cSynchronization - task register');

            // https://developer.wordpress.org/reference/functions/wp_schedule_single_event/
            wp_schedule_single_event(time(), 'termsRecount1cSynchronization');
        }
    }

    public function createCronDisableItems()
    {
        // https://developer.wordpress.org/reference/functions/wp_next_scheduled/
        if (!wp_next_scheduled('disableItems1cSynchronization')) {
            Logger::log('disableItems1cSynchronization - task register');

            // https://developer.wordpress.org/reference/functions/wp_schedule_single_event/
            wp_schedule_single_event(time() + 15, 'disableItems1cSynchronization');
        }
    }

    public function cronAction()
    {
        $response = Bootstrap::$common->requester->call('cron_code_check');

        if (is_wp_error($response)) {
            return;
        }

        if ($response->status === 'stop') {
            update_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, '');
        }
    }

    public function actionTermRecount()
    {
        global $wpdb;

        Logger::startProcessingRequestLogProtocolEntry(true);
        Logger::log('termsRecount1cSynchronization - started');

        delete_option('product_cat_children');

        $taxes = [
            'product_cat',
            'product_tag',
        ];

        foreach ($taxes as $tax) {
            // https://docs.woocommerce.com/wc-apidocs/function-_wc_term_recount.html
            _wc_term_recount(
                // https://developer.wordpress.org/reference/functions/get_terms/
                get_terms(
                    [
                        'taxonomy' => $tax,
                        'hide_empty' => false,
                        'fields' => 'id=>parent',
                    ]
                ),
                // https://developer.wordpress.org/reference/functions/get_taxonomy/
                get_taxonomy($tax),
                true,
                false
            );

            $this->recalculatePostCountInTax($tax);
        }

        // recalculate attribute terms post count
        if (function_exists('wc_get_attribute_taxonomies')) {
            $attributeTaxonomies = \wc_get_attribute_taxonomies();

            if ($attributeTaxonomies) {
                foreach ($attributeTaxonomies as $tax) {
                    // widget filter by attribute clean transient
                    \delete_transient('wc_layered_nav_counts_pa_' . $tax->attribute_name);

                    $this->recalculatePostCountInTax(
                        // https://docs.woocommerce.com/wc-apidocs/function-wc_attribute_taxonomy_name.html
                        \wc_attribute_taxonomy_name($tax->attribute_name)
                    );
                }
            }
        }

        /**
         * If the taxonomy is overridden, then the term counts need to be recalculated.
         *
         * @see ManufacturerProduct::resolveAttribute()
         */
        $manufacturerTaxonomy = \apply_filters('itglx/wc1c/catalog/import/product-manufacturer-taxonomy', '');

        if (!empty($manufacturerTaxonomy)) {
            Logger::log('[manufacturer] taxonomy redefined', [$manufacturerTaxonomy]);

            $this->recalculatePostCountInTax($manufacturerTaxonomy);
        }

        /**
         * If the taxonomy is overridden, then the term counts need to be recalculated.
         *
         * @see CountryProduct::resolveAttribute()
         */
        $countryTaxonomy = \apply_filters('itglx/wc1c/catalog/import/product-country-taxonomy', '');

        if (!empty($countryTaxonomy)) {
            Logger::log('[country] taxonomy redefined', [$countryTaxonomy]);

            $this->recalculatePostCountInTax($countryTaxonomy);
        }

        // update wc search/ordering table
        if (function_exists('wc_update_product_lookup_tables_column')) {
            // Make a row per product in lookup table.
            $wpdb->query(
                "
        		INSERT IGNORE INTO {$wpdb->wc_product_meta_lookup} (`product_id`)
        		SELECT
        			posts.ID
        		FROM {$wpdb->posts} posts
        		WHERE
        			posts.post_type IN ('product', 'product_variation')
        		"
            );

            // https://docs.woocommerce.com/wc-apidocs/function-wc_update_product_lookup_tables_column.html
            wc_update_product_lookup_tables_column('min_max_price');
            wc_update_product_lookup_tables_column('stock_quantity');
            wc_update_product_lookup_tables_column('sku');
            wc_update_product_lookup_tables_column('stock_status');
            wc_update_product_lookup_tables_column('total_sales');
            wc_update_product_lookup_tables_column('onsale');
            wc_update_product_lookup_tables_column('downloadable');
            wc_update_product_lookup_tables_column('virtual');

            Logger::log('update lookup');
        }

        // clear featured, sale and etc. transients
        if (function_exists('wc_delete_product_transients')) {
            Logger::log('execute - wc_delete_product_transients');

            // https://docs.woocommerce.com/wc-apidocs/function-wc_delete_product_transients.html
            wc_delete_product_transients();
        }

        // if activated Wp Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            Logger::log('execute - wp_cache_clear_cache');
            wp_cache_clear_cache();
        }

        // fixed compatibility with `Rank Math SEO`
        if (class_exists('\\RankMath')) {
            flush_rewrite_rules(true);
        }

        // fixed compatibility with `WooCommerce Wholesale Prices Premium`
        if (defined('WWPP_CRON_INITIALIZE_PRODUCT_WHOLESALE_VISIBILITY_FILTER')) {
            Logger::log('register task - ' . WWPP_CRON_INITIALIZE_PRODUCT_WHOLESALE_VISIBILITY_FILTER);

            wp_schedule_single_event(time(), WWPP_CRON_INITIALIZE_PRODUCT_WHOLESALE_VISIBILITY_FILTER);
        }

        /**
         * Fixed compatibility with `Premmerce Permalink Manager for WooCommerce`.
         *
         * This plugin connects hooks that listen for adding terms and posts only in the admin panel. For this reason,
         * for example, if a product category was created in the exchange, then when you open category page, it will be 404.
         * Therefore, we must force this plugin to react and reset the cache.
         *
         * @see https://wordpress.org/plugins/woo-permalink-manager/
         */
        if (class_exists('\\Premmerce\\UrlManager\\UrlManagerPlugin')) {
            Logger::log('used `Premmerce Permalink Manager for WooCommerce`, write option to flush rules');

            \update_option('premmerce_url_manager_flush_rules', true);
        }

        Logger::log('termsRecount1cSynchronization - end');

        Logger::endProcessingRequestLogProtocolEntry();
    }

    /**
     * @throws ProgressException
     */
    public function actionDisableItems()
    {
        Logger::startProcessingRequestLogProtocolEntry(true);
        Logger::log('disableItems1cSynchronization - started');

        DataDeletingOnFullExchange::products(false);
        DataDeletingOnFullExchange::categories(false);
        DataDeletingOnFullExchange::clearCache();

        // recalculate product cat counts
        $this->createCronTermRecount();

        Logger::log('disableItems1cSynchronization - end');
        Logger::endProcessingRequestLogProtocolEntry();
    }

    public function recalculatePostCountInTax($tax)
    {
        Logger::log('recalculate - ' . $tax);

        // https://developer.wordpress.org/reference/functions/get_terms/
        $terms = get_terms(
            [
                'taxonomy' => $tax,
                'hide_empty' => false,
                'fields' => 'ids',
            ]
        );

        if ($terms) {
            // https://developer.wordpress.org/reference/functions/wp_update_term_count_now/
            wp_update_term_count_now(
                $terms,
                $tax
            );
        }
    }
}
