<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class VariationOfferStocks extends OfferStocks
{
    /**
     * Write of stock values, as well as actions based on the stock.
     *
     * Visibility of variation, enabling and disabling stock management.
     *
     * @param int   $variationId     Variation ID.
     * @param array $stockData       {@see resolve()}
     * @param int   $parentProductID Parent ID of the variation.
     */
    public static function set($variationId, $stockData, $parentProductID)
    {
        $products1cStockNull = SettingsHelper::get('products_stock_null_rule', '0');

        ProductVariation::saveMetaValue($variationId, '_stock', $stockData['_stock'], $parentProductID);
        ProductVariation::saveMetaValue($variationId, '_separate_warehouse_stock', $stockData['_separate_warehouse_stock'], $parentProductID);

        Logger::log(
            '(variation) updated stock set for ID - '
            . $variationId
            . ', parent ID - '
            . $parentProductID,
            [$stockData['_stock'], get_post_meta($variationId, '_id_1c', true)]
        );

        if (!isset($_SESSION['IMPORT_1C']['variableVisibility'][$parentProductID])) {
            $_SESSION['IMPORT_1C']['variableVisibility'][$parentProductID] = [];
        }

        // resolve stock status
        if (!self::resolveHide($products1cStockNull, $stockData, $variationId, $parentProductID)) {
            if (self::resolveDisableManageStock($products1cStockNull, $stockData, $variationId, $parentProductID)) {
                ProductVariation::saveMetaValue($variationId, '_manage_stock', 'no', $parentProductID);
            } else {
                ProductVariation::saveMetaValue($variationId, '_manage_stock', get_option('woocommerce_manage_stock'), $parentProductID);
            }

            // enable variation
            ProductVariation::enable($variationId);

            $stockStatus = apply_filters(
                'itglx_wc1c_stock_status_value_if_not_hide',
                self::resolveStockStatus($products1cStockNull, $stockData),
                $stockData['_stock'],
                $variationId,
                $parentProductID
            );

            Product::show($variationId, true, $stockStatus);

            $backorders = null;

            if ($stockData['_stock'] > 0) {
                $backorders = SettingsHelper::get('products_onbackorder_stock_positive_rule', 'no');
            } elseif ($products1cStockNull === 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_notify') {
                $backorders = 'notify';
            } elseif ($products1cStockNull === 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_yes') {
                $backorders = 'yes';
            }

            /**
             * Filters the value to allow backorders.
             *
             * @since 1.97.0
             *
             * @param null|string $backorders
             * @param int         $variationId
             * @param int         $parentProductID Parent ID of the variation.
             * @param array       $stockData       {@see resolve()}
             */
            $backorders = \apply_filters('itglx_wc1c_stock_value_backorders', $backorders, $variationId, $parentProductID, $stockData);

            // set backorders value
            if ($backorders !== null) {
                ProductVariation::saveMetaValue($variationId, '_backorders', $backorders, $parentProductID);
            }

            $_SESSION['IMPORT_1C']['variableVisibility'][$parentProductID][] = $stockStatus;
        } else {
            $_SESSION['IMPORT_1C']['variableVisibility'][$parentProductID][] = 'outofstock';

            // 2 - "Do not hide, but do not give the opportunity to put in the basket"
            if ($products1cStockNull === '2') {
                ProductVariation::enable($variationId);
            } else {
                ProductVariation::disable($variationId);
            }

            // disable the backorders option, as it could have been enabled earlier for the variation
            ProductVariation::saveMetaValue($variationId, '_backorders', 'no', $parentProductID);

            // has logic with $products1cStockNull = 2
            Product::hide($variationId, true);
        }

        self::actionAfterSetStocks($variationId, $stockData['_stock'], $parentProductID, $stockData);
    }
}
