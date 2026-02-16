<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Parsing offer (`Предложение`) for a variable product.
 */
class VariationOffer
{
    /**
     * @param \SimpleXMLElement $element     'Предложение' node object.
     * @param string            $productGuid
     * @param float             $rate
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function process(\SimpleXMLElement $element, $productGuid, $rate)
    {
        $productID = self::getParentProduct($productGuid, $element);

        if (empty($productID)) {
            Logger::log('(variation) not exists parent product', [(string) $element->Ид], 'warning');

            return;
        }

        self::setHasVariationState($productID);

        $variationID = ProductVariation::getIdByMeta((string) $element->Ид);

        /**
         * Filters the sign when an variation offer is considered deleted.
         *
         * @since 1.70.3
         *
         * @param bool              $isRemoved   Default: false.
         * @param \SimpleXMLElement $element
         * @param int               $variationID
         * @param int               $productID   Parent product ID.
         *
         * @see OfferIsRemoved
         */
        $offerIsRemoved = \apply_filters('itglx_wc1c_variation_offer_is_removed', false, $element, $variationID, $productID);

        /*
         * If offer is marked for deletion:
         * - there is no variation - then skip.
         * - there is a variation and deletion is enabled - then remove and skip.
        */
        if ($offerIsRemoved) {
            Logger::log('(variation) offer is marked for deletion', [$variationID, (string) $element->Ид]);

            if (empty($variationID) || !SettingsHelper::isEmpty('offers_delete_variation_if_offer_marked_deletion')) {
                if ($variationID) {
                    ProductVariation::remove($variationID, $productID);
                }

                return;
            }
        }

        /**
         * Filters the content of a variation offer before processing.
         *
         * It may be useful to change or add data for the main logic, if it is not possible
         * to do this in 1C, for example, for configuration "Розница", if the characteristics are
         * not unloaded.
         *
         * @since 1.50.2
         * @since 1.124.0 The `$variationID` and `$productID` parameter was added.
         *
         * @param \SimpleXMLElement $element
         * @param int               $variationID
         * @param int               $productID   Parent product ID.
         */
        $element = \apply_filters('itglx_wc1c_variation_offer_xml_data', $element, $variationID, $productID);

        // if something was wrong returned from the filter
        if (!$element instanceof \SimpleXMLElement) {
            return;
        }

        // prevent search variation if not exists
        if (!$variationID) {
            $variationID = \apply_filters('itglx_wc1c_find_product_variation_id', $variationID, $productID, $element);

            if ($variationID) {
                Logger::log('(variation) filter `itglx_wc1c_find_product_variation_id`, `ID` - ' . $variationID, [(string) $element->Ид]);
                ProductVariation::saveMetaValue($variationID, '_id_1c', (string) $element->Ид, $productID);
            }
        }

        // resolve main variation data
        if (
            VariationOfferAttributes::hasOptions($element)
            || VariationOfferAttributes::hasCharacteristics($element)
        ) {
            $variationEntry = ProductVariation::mainData(
                $element,
                [
                    'ID' => $variationID,
                    'post_parent' => $productID,
                ]
            );

            $variationID = !$variationID && !empty($variationEntry['ID']) ? $variationEntry['ID'] : $variationID;

            // if offer is marked for deletion and there is a variation, then turn it off
            if ($offerIsRemoved && !empty($variationID)) {
                Logger::log('(variation) disabled - ' . $variationID, [(string) $element->Ид]);
                ProductVariation::disable($variationID);
            }
        }

        if (empty($variationID)) {
            Logger::log('(variation) not exists variation by offer id', [(string) $element->Ид], 'warning');

            return;
        }

        if (
            !$offerIsRemoved
            && !VariationOfferPrices::isDisabled()
            && VariationOfferPrices::offerHasPriceData($element)
        ) {
            if (OfferPrices::changeControl($element, $productID, $variationID)) {
                Logger::log('(variation) price change control - no changes, ID - ' . $variationID, [(string) $element->Ид]);
            } else {
                VariationOfferPrices::setPrices(
                    VariationOfferPrices::resolvePrices($element, $rate),
                    $variationID,
                    $productID
                );
            }
        }

        if (
            !$offerIsRemoved
            && !VariationOfferStocks::isDisabled()
            && VariationOfferStocks::offerHasStockData($element)
        ) {
            if (!\apply_filters('itglx_wc1c_ignore_offer_set_stock_data', false, $variationID, $productID)) {
                VariationOfferStocks::set($variationID, VariationOfferStocks::resolve($element), $productID);
            } else {
                Logger::log(
                    '(variation) ignore set stock data by filter - itglx_wc1c_ignore_offer_set_stock_data',
                    [(string) $element->Ид]
                );
            }
        }

        /**
         * Fires after processing a variation offer.
         *
         * @since 1.9.1
         *
         * @param int               $variationID
         * @param int               $productID
         * @param \SimpleXMLElement $element
         */
        \do_action('itglx_wc1c_after_variation_offer_resolve', $variationID, $productID, $element);
    }

    /**
     * @param string            $guid
     * @param \SimpleXMLElement $element
     *
     * @return null|int Product ID or null if there is no product.
     */
    private static function getParentProduct($guid, $element)
    {
        if (!isset($_SESSION['IMPORT_1C']['productParent'])) {
            $_SESSION['IMPORT_1C']['productParent'] = [];
        }

        if (isset($_SESSION['IMPORT_1C']['productParent'][$guid])) {
            return $_SESSION['IMPORT_1C']['productParent'][$guid];
        }

        $productID = Product::getSiteProductId($element, $guid);

        if (empty($productID)) {
            return null;
        }

        $_SESSION['IMPORT_1C']['productParent'][$guid] = $productID;

        return $productID;
    }

    /**
     * @param int $productID
     *
     * @return void
     */
    private static function setHasVariationState($productID)
    {
        if (!isset($_SESSION['IMPORT_1C']['hasVariation'])) {
            $_SESSION['IMPORT_1C']['hasVariation'] = [];
        }

        $_SESSION['IMPORT_1C']['hasVariation'][$productID] = true;
    }
}
