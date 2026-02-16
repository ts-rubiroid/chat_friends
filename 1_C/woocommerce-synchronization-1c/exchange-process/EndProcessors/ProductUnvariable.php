<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class ProductUnvariable
{
    /**
     * @return void
     *
     * @throws ProgressException
     */
    public static function process()
    {
        if (empty($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'])) {
            return;
        }

        Logger::log('maybe unvariable start');

        \wp_suspend_cache_addition(true);

        $_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers']
            = array_unique($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers']);

        foreach ($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'] as $key => $productID) {
            if (HeartBeat::limitIsExceeded()) {
                Logger::log('maybe unvariable - progress');

                throw new ProgressException('maybe unvariable process...');
            }

            // if product has variations in current exchange
            if (isset($_SESSION['IMPORT_1C']['hasVariation'], $_SESSION['IMPORT_1C']['hasVariation'][$productID])) {
                unset($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'][$key]);

                continue;
            }

            // product is not variable
            if (!get_post_meta($productID, '_is_set_variable', true)) {
                unset($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'][$key]);

                continue;
            }

            Logger::log(
                '(product) unvariable processing product, ID - ' . $productID,
                [get_post_meta($productID, '_id_1c', true)]
            );

            delete_post_meta($productID, '_is_set_variable');

            Term::setObjectTerms($productID, 'simple', 'product_type');
            self::cleanVariations($productID);
            Product::saveMetaValue($productID, '_regular_price', get_post_meta($productID, '_price', true));

            unset($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'][$key]);
        }

        Logger::log('maybe unvariable end');

        \wp_suspend_cache_addition(false);
    }

    /**
     * @param int $productID
     *
     * @see https://developer.wordpress.org/reference/functions/wp_parse_id_list/
     *
     * @return void
     */
    private static function cleanVariations($productID)
    {
        $variationIds = wp_parse_id_list(
            get_posts(
                [
                    'post_parent' => $productID,
                    'post_type' => 'product_variation',
                    'fields' => 'ids',
                    'post_status' => ['any', 'trash', 'auto-draft'],
                    'numberposts' => -1,
                ]
            )
        );

        if (!empty($variationIds)) {
            foreach ($variationIds as $variationId) {
                ProductVariation::remove($variationId);
            }
        } else {
            Logger::log('(product) has no variations', [get_post_meta($productID, '_id_1c', true)]);
        }

        delete_transient('wc_product_children_' . $productID);
    }
}
