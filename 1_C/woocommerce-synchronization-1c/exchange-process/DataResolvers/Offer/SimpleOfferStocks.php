<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SimpleOfferStocks extends OfferStocks
{
    /**
     * Write of stock values, as well as actions based on the stock.
     *
     * Visibility of product, enabling and disabling stock management.
     *
     * @param int   $productId Product ID.
     * @param array $stockData {@see resolve()}
     */
    public static function set($productId, $stockData)
    {
        $products1cStockNull = SettingsHelper::get('products_stock_null_rule', '0');

        // simple product and stock is changed
        if (get_post_meta($productId, '_stock', true) != $stockData['_stock']) {
            $_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'][] = $productId;
        }

        Product::saveMetaValue($productId, '_stock', $stockData['_stock']);
        Product::saveMetaValue($productId, '_separate_warehouse_stock', $stockData['_separate_warehouse_stock']);

        Logger::log(
            '(product) updated stock set for ID - ' . $productId,
            [$stockData['_stock'], get_post_meta($productId, '_id_1c', true)]
        );

        // resolve stock status
        if (!self::resolveHide($products1cStockNull, $stockData, $productId)) {
            if (self::resolveDisableManageStock($products1cStockNull, $stockData, $productId)) {
                Product::saveMetaValue($productId, '_manage_stock', 'no');
            } else {
                Product::saveMetaValue($productId, '_manage_stock', get_option('woocommerce_manage_stock'));
            }

            Product::show(
                $productId,
                true,
                apply_filters(
                    'itglx_wc1c_stock_status_value_if_not_hide',
                    self::resolveStockStatus($products1cStockNull, $stockData),
                    $stockData['_stock'],
                    $productId,
                    null
                )
            );

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
             * @param int         $productId
             * @param null        $parentProductID
             * @param array       $stockData       {@see resolve()}
             */
            $backorders = \apply_filters('itglx_wc1c_stock_value_backorders', $backorders, $productId, null, $stockData);

            // set backorders value
            if ($backorders !== null) {
                Product::saveMetaValue($productId, '_backorders', $backorders);
            }
        } else {
            // disable the backorders option, as it could have been enabled earlier for the product
            Product::saveMetaValue($productId, '_backorders', 'no');

            // has logic with $products1cStockNull = 2
            Product::hide($productId, true);
        }

        self::actionAfterSetStocks($productId, $stockData['_stock'], null, $stockData);
    }
}
