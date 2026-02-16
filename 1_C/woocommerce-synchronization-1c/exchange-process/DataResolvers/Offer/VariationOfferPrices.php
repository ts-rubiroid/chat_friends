<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class VariationOfferPrices extends OfferPrices
{
    /**
     * @param array $resolvePrices
     * @param int   $variationID
     * @param int   $productID
     */
    public static function setPrices($resolvePrices, $variationID, $productID)
    {
        $priceWorkRule = SettingsHelper::get('price_work_rule', 'regular');
        $priceValue = (float) $resolvePrices['regular'];

        ProductVariation::saveMetaValue($variationID, '_all_prices', $resolvePrices['all'], $productID);

        if (!$priceValue) {
            Logger::log(
                '(variation) empty price value - skip - for ID - ' . $variationID . ', parent ID - ' . $productID,
                [$priceValue, get_post_meta($variationID, '_id_1c', true)]
            );

            \do_action('itglx_wc1c_product_or_variation_has_empty_price', $variationID, $productID);
            self::actionAfterSetPrices($variationID, $priceValue, $priceWorkRule);

            return;
        }

        self::saveRegularPrice($priceValue, $variationID, $productID);

        switch ($priceWorkRule) {
            case 'regular':
                if (!SettingsHelper::isEmpty('remove_sale_price')) {
                    ProductVariation::saveMetaValue($variationID, '_sale_price', '', $productID);

                    Logger::log(
                        '(variation) clean `_sale_price` (as enabled - remove_sale_price) for ID - '
                        . $variationID
                        . ', parent ID - '
                        . $productID,
                        [$priceValue, get_post_meta($variationID, '_id_1c', true)]
                    );
                }

                $salePrice = get_post_meta($variationID, '_sale_price', true);

                if ((float) $salePrice <= 0) {
                    self::savePrice($priceValue, $variationID, $productID);
                }

                break;
            case 'regular_and_sale':
                $salePrice = '';

                if (
                    !empty($resolvePrices['sale'])
                    && (float) $resolvePrices['sale'] !== $priceValue
                ) {
                    $salePrice = $resolvePrices['sale'];
                }

                self::saveSalePrice($salePrice, $variationID, $productID);

                if ((float) $salePrice <= 0) {
                    self::savePrice($priceValue, $variationID, $productID);
                } else {
                    self::savePrice($salePrice, $variationID, $productID);
                }

                break;
            case 'regular_and_show_list':
            case 'regular_and_show_list_and_apply_price_depend_cart_totals':
                $salePrice = get_post_meta($variationID, '_sale_price', true);

                if ((float) $salePrice <= 0) {
                    self::savePrice($priceValue, $variationID, $productID);
                }

                break;
            default:
                // Nothing
                break;
        }

        self::actionAfterSetPrices($variationID, $priceValue, $priceWorkRule);
    }

    /**
     * @param float|int $value
     * @param int       $variationID
     * @param int       $productID
     */
    private static function saveRegularPrice($value, $variationID, $productID)
    {
        ProductVariation::saveMetaValue($variationID, '_regular_price', $value, $productID);

        Logger::log(
            '(variation) updated `_regular_price` for ID - ' . $variationID . ', parent ID - ' . $productID,
            [$value, get_post_meta($variationID, '_id_1c', true)]
        );
    }

    /**
     * @param float|int $value
     * @param int       $variationID
     * @param int       $productID
     */
    private static function savePrice($value, $variationID, $productID)
    {
        ProductVariation::saveMetaValue($variationID, '_price', $value, $productID);

        Logger::log(
            '(variation) updated `_price` for ID - ' . $variationID . ', parent ID - ' . $productID,
            [$value, get_post_meta($variationID, '_id_1c', true)]
        );
    }

    /**
     * @param float|int $value
     * @param int       $variationID
     * @param int       $productID
     */
    private static function saveSalePrice($value, $variationID, $productID)
    {
        ProductVariation::saveMetaValue($variationID, '_sale_price', $value, $productID);

        Logger::log(
            '(variation) updated `_sale_price` for ID - ' . $variationID . ', parent ID - ' . $productID,
            [$value, get_post_meta($variationID, '_id_1c', true)]
        );
    }
}
