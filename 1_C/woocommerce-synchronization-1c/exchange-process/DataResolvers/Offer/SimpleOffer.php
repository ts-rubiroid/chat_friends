<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Parsing offer (`Предложение`) for a simple product.
 */
class SimpleOffer
{
    /**
     * @param \SimpleXMLElement $element 'Предложение' node object.
     * @param float             $rate
     *
     * @return void
     */
    public static function process(\SimpleXMLElement $element, $rate)
    {
        if (!isset($_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'])) {
            $_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'] = [];
        }

        $productId = Product::getSiteProductId($element, (string) $element->Ид);

        // ignore simple offer if product is variable
        if ($productId && get_post_meta($productId, '_is_set_variable', true)) {
            Logger::log('(product) ignore `simple` offer for `variable`', [(string) $element->Ид]);

            $_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'][] = $productId;

            return;
        }

        /**
         * Filters the sign when an simple offer is considered deleted.
         *
         * @since 1.106.0
         *
         * @param bool              $isRemoved Default: false.
         * @param \SimpleXMLElement $element
         * @param int               $productId
         *
         * @see OfferIsRemoved
         */
        $offerIsRemoved = \apply_filters('itglx/wc1c/catalog/import/offer/simple/is-removed', false, $element, $productId);

        if ($offerIsRemoved) {
            Logger::log('(product) offer is marked for deletion', [$productId, (string) $element->Ид]);

            return;
        }

        if (empty($productId)) {
            Logger::log('(product) not exists product by offer id', [(string) $element->Ид], 'warning');

            return;
        }

        $_SESSION['IMPORT_1C_PROCESS']['allCurrentProductIdBySimpleOffers'][] = $productId;

        if (!isset($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'])) {
            $_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'] = [];
        }

        if (!SimpleOfferPrices::isDisabled() && SimpleOfferPrices::offerHasPriceData($element)) {
            if (OfferPrices::changeControl($element, $productId)) {
                Logger::log('(product) price change control - no changes, ID - ' . $productId, [(string) $element->Ид]);
            } else {
                SimpleOfferPrices::setPrices(SimpleOfferPrices::resolvePrices($element, $rate), $productId);
            }
        }

        if (!SimpleOfferStocks::isDisabled() && SimpleOfferStocks::offerHasStockData($element)) {
            if (!\apply_filters('itglx_wc1c_ignore_offer_set_stock_data', false, $productId, null)) {
                SimpleOfferStocks::set($productId, SimpleOfferStocks::resolve($element));
            } else {
                Logger::log(
                    '(product) ignore set stock data by filter - itglx_wc1c_ignore_offer_set_stock_data',
                    [(string) $element->Ид]
                );
            }
        }

        /**
         * Fires after processing a simple offer.
         *
         * @since 1.9.1
         *
         * @param int               $productId
         * @param \SimpleXMLElement $element
         */
        \do_action('itglx_wc1c_after_product_offer_resolve', $productId, $element);
    }
}
