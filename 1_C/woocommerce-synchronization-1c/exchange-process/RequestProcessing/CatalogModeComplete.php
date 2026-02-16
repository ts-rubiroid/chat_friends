<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\DataDeletingOnFullExchange;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Cron;

class CatalogModeComplete
{
    public static function process()
    {
        if (!get_option('not_clear_1c_complete')) {
            DataDeletingOnFullExchange::clearCache();

            $cron = Cron::getInstance();
            $cron->createCronTermRecount();

            // clear sitemap cache
            if (class_exists('\\WPSEO_Sitemaps_Cache')) {
                remove_filter('wpseo_enable_xml_sitemap_transient_caching', '__return_false');

                /** @psalm-suppress UndefinedClass */
                \WPSEO_Sitemaps_Cache::clear();
            }
        } else {
            update_option('not_clear_1c_complete', '');
        }

        SuccessResponse::getInstance()->send(esc_html__('Package complete!', 'itgalaxy-woocommerce-1c'));
    }
}
