<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SimpleOfferPrices extends OfferPrices
{
    /**
     * @param array $resolvePrices
     * @param int   $productId
     */
    public static function setPrices($resolvePrices, $productId)
    {
        $priceWorkRule = SettingsHelper::get('price_work_rule', 'regular');
        $priceValue = (float) $resolvePrices['regular'];

        // price is changed
        if (get_post_meta($productId, '_regular_price', true) != $resolvePrices['regular']) {
            $_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'][] = $productId;
        }

        Product::saveMetaValue($productId, '_all_prices', $resolvePrices['all']);

        if (!$priceValue) {
            Logger::log(
                '(product) empty price value - skip - for ID - ' . $productId,
                [$priceValue, get_post_meta($productId, '_id_1c', true)]
            );

            \do_action('itglx_wc1c_product_or_variation_has_empty_price', $productId, null);
            self::actionAfterSetPrices($productId, $priceValue, $priceWorkRule);

            return;
        }

        self::saveRegularPrice($priceValue, $productId);

        switch ($priceWorkRule) {
            case 'regular':
                if (!SettingsHelper::isEmpty('remove_sale_price')) {
                    Product::saveMetaValue($productId, '_sale_price', '');

                    Logger::log(
                        '(product) clean `_sale_price` (as enabled - remove_sale_price) for ID - ' . $productId,
                        [$priceValue, get_post_meta($productId, '_id_1c', true)]
                    );
                }

                $salePrice = get_post_meta($productId, '_sale_price', true);

                if ((float) $salePrice <= 0) {
                    self::savePrice($priceValue, $productId);
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

                self::saveSalePrice($salePrice, $productId);

                if ((float) $salePrice <= 0) {
                    self::savePrice($priceValue, $productId);
                } else {
                    self::savePrice($salePrice, $productId);
                }

                break;
            case 'regular_and_show_list':
            case 'regular_and_show_list_and_apply_price_depend_cart_totals':
                $salePrice = get_post_meta($productId, '_sale_price', true);

                if ((float) $salePrice <= 0) {
                    self::savePrice($priceValue, $productId);
                }

                break;
            default:
                // Nothing
                break;
        }

        self::actionAfterSetPrices($productId, $priceValue, $priceWorkRule);
    }

    /**
     * @param float|int $value
     * @param int       $productId
     */
    private static function saveRegularPrice($value, $productId)
    {
        Product::saveMetaValue($productId, '_regular_price', $value);

        Logger::log(
            '(product) updated `_regular_price` for ID - ' . $productId,
            [$value, get_post_meta($productId, '_id_1c', true)]
        );
    }

    /**
     * @param float|int $value
     * @param int       $productId
     */
    private static function savePrice($value, $productId)
    {
        Product::saveMetaValue($productId, '_price', $value);

        Logger::log(
            '(product) updated `_price` for ID - ' . $productId,
            [$value, get_post_meta($productId, '_id_1c', true)]
        );
    }

    /**
     * @param float|int $value
     * @param int       $productId
     */
    private static function saveSalePrice($value, $productId)
    {
        Product::saveMetaValue($productId, '_sale_price', $value);

        Logger::log(
            '(product) updated `_sale_price` for ID - ' . $productId,
            [$value, get_post_meta($productId, '_id_1c', true)]
        );
    }
}
