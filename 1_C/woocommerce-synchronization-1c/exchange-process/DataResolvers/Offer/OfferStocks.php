<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class OfferStocks
{
    /**
     * Parsing information about the stock according to the offer data.
     *
     * @param \SimpleXMLElement $element Node `Предложение` object.
     *
     * @return array The result of parsing the offer stock data, key `_stock` contains (float) the total value of the stock,
     *               and key `_separate_warehouse_stock` contains (array) the stock with separate by warehouses.
     */
    public static function resolve(\SimpleXMLElement $element)
    {
        if (self::isDisabled()) {
            return [
                '_stock' => 0,
                '_separate_warehouse_stock' => [],
            ];
        }

        $separateStock = self::resolveSeparate($element);
        $stock = 0;

        if (!empty($separateStock) || !SettingsHelper::isEmpty('offers_warehouses_account_stock_rule')) {
            $stock = array_sum($separateStock);
        } elseif (isset($element->Количество)) {
            $stock = Helper::toFloat($element->Количество);
        }
        // schema 3.1
        elseif (isset($element->Остатки, $element->Остатки->Остаток, $element->Остатки->Остаток->Количество)) {
            foreach ($element->Остатки->Остаток as $stockElement) {
                $stock += Helper::toFloat($stockElement->Количество);
            }
        }

        return [
            '_stock' => $stock,
            '_separate_warehouse_stock' => $separateStock,
        ];
    }

    /**
     * Resolve of information on stocks with division by warehouses.
     *
     * @param \SimpleXMLElement $element Node `Предложение` object
     *
     * @return array The result of parsing data on the separate of stocks by warehouses. In the form of an array,
     *               where the key is the warehouse id, and the value is the stock in this warehouse.
     */
    public static function resolveSeparate(\SimpleXMLElement $element)
    {
        $stocks = [];

        if (isset($element->Склад)) {
            foreach ($element->Склад as $store) {
                if (!isset($stocks[(string) $store['ИдСклада']])) {
                    $stocks[(string) $store['ИдСклада']] = 0;
                }

                $stocks[(string) $store['ИдСклада']] += Helper::toFloat($store['КоличествоНаСкладе']);
            }
        } elseif (isset($element->Склады)) {
            foreach ($element->Склады as $store) {
                if (!isset($stocks[(string) $store['ИдСклада']])) {
                    $stocks[(string) $store['ИдСклада']] = 0;
                }

                $stocks[(string) $store['ИдСклада']] += Helper::toFloat($store['КоличествоНаСкладе']);
            }
        }
        // schema 3.1
        elseif (
            isset($element->Остатки, $element->Остатки->Остаток, $element->Остатки->Остаток->Склад)
        ) {
            foreach ($element->Остатки->Остаток as $stockElement) {
                if (!isset($stocks[(string) $stockElement->Склад->Ид])) {
                    $stocks[(string) $stockElement->Склад->Ид] = 0;
                }

                $stocks[(string) $stockElement->Склад->Ид] += Helper::toFloat($stockElement->Склад->Количество);
            }
        } elseif (
            isset($element->КоличествоНаСкладах, $element->КоличествоНаСкладах->КоличествоНаСкладе)
        ) {
            foreach ($element->КоличествоНаСкладах->КоличествоНаСкладе as $stockElement) {
                $stockID = isset($stockElement->ИдСклада)
                    ? (string) $stockElement->ИдСклада
                    : (string) $stockElement->Ид;

                if (!isset($stocks[$stockID])) {
                    $stocks[$stockID] = 0;
                }

                $stocks[$stockID] += Helper::toFloat($stockElement->Количество);
            }
        }
        // SBIS structure
        elseif (isset($element->ОстаткиПоСкладу, $element->ОстаткиПоСкладу->ОстаткиПоСкладу)) {
            foreach ($element->ОстаткиПоСкладу->ОстаткиПоСкладу as $stockElement) {
                if (!isset($stocks[(string) $stockElement->ИдСклада])) {
                    $stocks[(string) $stockElement->ИдСклада] = 0;
                }

                $stocks[(string) $stockElement->ИдСклада] += Helper::toFloat($stockElement->КоличествоНаСкладе);
            }
        }

        if (!SettingsHelper::isEmpty('offers_warehouses_account_stock_rule')) {
            $warehouses = SettingsHelper::get('offers_warehouses_selected_to_account_stock', []);
            $rule = SettingsHelper::get('offers_warehouses_account_stock_rule');

            if ($rule === 'selected') {
                foreach ($stocks as $guid => $_) {
                    if (!in_array($guid, $warehouses, true)) {
                        $stocks[$guid] = 0;
                    }
                }
            } elseif ($rule === 'not_selected') {
                foreach ($stocks as $guid => $_) {
                    if (in_array($guid, $warehouses, true)) {
                        $stocks[$guid] = 0;
                    }
                }
            }
        }

        return $stocks;
    }

    /**
     * The method allows to determine whether the offer contains data on stock.
     *
     * @param \SimpleXMLElement $element Node `Предложение` object
     *
     * @return bool
     */
    public static function offerHasStockData(\SimpleXMLElement $element)
    {
        if (
            isset($element->Остатки)
            || isset($element->КоличествоНаСкладах)
            || isset($element->Количество)
            // the old exchange may not contain a stock node when the value is 0
            || (
                !isset($_GET['version'])
                && isset($element->Наименование)
                && OfferPrices::offerHasPriceData($element)
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Checking whether the processing of stocks is disabled in the settings.
     *
     * @return bool
     */
    public static function isDisabled(): bool
    {
        return !SettingsHelper::isEmpty('skip_product_stocks');
    }

    /**
     * The method allows to get what status of the stock should be set.
     *
     * @param string $products1cStockNull Rule of operation when the stock is less than or equal to zero.
     * @param array  $stockData           {@see resolve()}
     *
     * @return string The value of the stock status.
     */
    protected static function resolveStockStatus($products1cStockNull, $stockData)
    {
        if ($stockData['_stock'] > 0) {
            return 'instock';
        }

        if ($products1cStockNull === 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_notify') {
            return 'onbackorder';
        }

        if ($products1cStockNull === 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_yes') {
            return 'onbackorder';
        }

        if ($products1cStockNull !== 'not_hide_and_put_basket_with_disable_manage_stock_and_stock_status_onbackorder') {
            return 'instock';
        }

        return 'onbackorder';
    }

    /**
     * The method allows to determine whether a product / variation should be hidden or not.
     *
     * @param string   $products1cStockNull Rule of operation when the stock is less than or equal to zero.
     * @param array    $stockData
     * @param int      $productId           Product ID (if simple) or variation ID.
     * @param null|int $parentProductID     If a simple product, then null, otherwise the product ID of the parent of the variation.
     *
     * @return bool
     */
    protected static function resolveHide($products1cStockNull, $stockData, $productId, $parentProductID = null)
    {
        $hide = true;

        switch ($products1cStockNull) {
            case '0':
                $hide = $stockData['_stock'] <= 0;
                break;
            case '1':
                $hide = false;
                break;
            case 'not_hide_and_put_basket_with_disable_manage_stock_and_stock_status_onbackorder':
            case 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_notify':
            case 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_yes':
                $hide = false;
                break;
            case '2':
                $hide = $stockData['_stock'] <= 0;
                break;
            case 'with_negative_not_hide_and_put_basket_with_zero_hide_and_not_put_basket':
                // not a strict comparison is needed, since on the left is float
                $hide = $stockData['_stock'] == 0;
                break;
            default:
                // Nothing
                break;
        }

        // if the price is empty, hide in any case
        if (SettingsHelper::isEmpty('offers_do_not_check_has_price_in_stock_behavior')) {
            $hide = !get_post_meta($productId, '_price', true) ? true : $hide;
        }

        if ($parentProductID) {
            $hide = (bool) apply_filters(
                'itglx_wc1c_hide_variation_by_stock_value',
                $hide,
                $stockData['_stock'],
                $productId,
                $parentProductID
            );
        } else {
            $hide = (bool) apply_filters(
                'itglx_wc1c_hide_product_by_stock_value',
                $hide,
                $stockData['_stock'],
                $productId
            );
        }

        return $hide;
    }

    /**
     * The method allows to determine whether the stock management for a product / variation should be disabled or not.
     *
     * @param string   $products1cStockNull Rule of operation when the stock is less than or equal to zero.
     * @param array    $stockData
     * @param int      $productId           Product ID (if simple) or variation ID.
     * @param null|int $parentProductID     If a simple product, then false, otherwise the product ID of the parent of the variation.
     *
     * @return bool
     */
    protected static function resolveDisableManageStock($products1cStockNull, $stockData, $productId, $parentProductID = null)
    {
        $disableManageStock = false;

        switch ($products1cStockNull) {
            case '0':
                // Nothing
                break;
            case '1':
                $disableManageStock = $stockData['_stock'] <= 0;
                break;
            case 'not_hide_and_put_basket_with_disable_manage_stock_and_stock_status_onbackorder':
                $disableManageStock = $stockData['_stock'] <= 0;
                break;
            case '2':
            case 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_notify':
            case 'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_yes':
                // Nothing
                break;
            case 'with_negative_not_hide_and_put_basket_with_zero_hide_and_not_put_basket':
                $disableManageStock = $stockData['_stock'] < 0;
                break;
            default:
                // Nothing
                break;
        }

        if ($parentProductID) {
            $disableManageStock = (bool) apply_filters(
                'itglx_wc1c_disable_manage_stock_variation_by_stock_value',
                $disableManageStock,
                $stockData['_stock'],
                $productId,
                $parentProductID
            );
        } else {
            $disableManageStock = (bool) apply_filters(
                'itglx_wc1c_disable_manage_stock_product_by_stock_value',
                $disableManageStock,
                $stockData['_stock'],
                $productId
            );
        }

        return $disableManageStock;
    }

    /**
     * @param int      $productOrVariationId
     * @param float    $stockValue
     * @param null|int $parentProductId
     * @param array    $stockData
     */
    protected static function actionAfterSetStocks($productOrVariationId, $stockValue, $parentProductId, $stockData)
    {
        /**
         * Fires after write of the stock data of the product / variation.
         *
         * By the time of the call, all actions, including setting the visibility and stock managing, have already been performed.
         *
         * @since 1.58.1
         * @since 1.90.1 The `$stockData` parameter was added
         *
         * @param int      $productOrVariationId Product ID (if simple) or variation ID.
         * @param float    $stockValue           Current stock value.
         * @param null|int $parentProductId      If a simple product, then false, otherwise the product ID of the parent of the variation.
         * @param array    $stockData            {@see resolve()}
         */
        \do_action('itglx_wc1c_after_set_product_stock', $productOrVariationId, $stockValue, $parentProductId, $stockData);
    }
}
