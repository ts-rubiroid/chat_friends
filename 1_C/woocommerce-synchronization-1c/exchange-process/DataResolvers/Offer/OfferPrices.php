<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class OfferPrices
{
    /**
     * @param \SimpleXMLElement $element Node `Предложение` object
     * @param float|int         $rate
     *
     * @return array
     */
    public static function resolvePrices($element, $rate)
    {
        if (self::isDisabled() || !self::offerHasPriceData($element)) {
            return ['regular' => '', 'sale' => '', 'all' => []];
        }

        $allPriceTypes = \get_option('all_prices_types', []);

        // if there is no price types
        if (empty($allPriceTypes)) {
            return ['regular' => '', 'sale' => '', 'all' => []];
        }

        $basePriceType = SettingsHelper::get('price_type_1', '');

        /**
         * if the price type is not specified in the settings or the specified price type is not in the current set,
         * then use the first.
         */
        if (empty($basePriceType) || !isset($allPriceTypes[$basePriceType])) {
            $priceType = array_shift($allPriceTypes);
            $basePriceType = $priceType['id'];
        }

        $allPrices = [];

        /**
         * Example xml structure.
         *
         * <Цены>
         *     <Цена>
         *         <Представление>1 890 руб. за шт</Представление>
         *         <ИдТипаЦены>a90d8a18-3a25-11eb-1d95-e0d55e5d9bf9</ИдТипаЦены>
         *         <ЦенаЗаЕдиницу>1890</ЦенаЗаЕдиницу>
         *         <Валюта>руб</Валюта>
         *         <Единица>шт</Единица>
         *         <Коэффициент>1</Коэффициент>
         *     </Цена>
         *     <Цена>
         *         <Представление>950 руб. за шт</Представление>
         *        <ИдТипаЦены>d6677f77-e71a-11ea-9c50-f60d71ff655c</ИдТипаЦены>
         *        <ЦенаЗаЕдиницу>950</ЦенаЗаЕдиницу>
         *        <Валюта>руб</Валюта>
         *        <Единица>шт</Единица>
         *        <Коэффициент>1</Коэффициент>
         *     </Цена>
         * </Цены>
         */
        foreach ($element->Цены->Цена as $price) {
            $priceType = (string) $price->ИдТипаЦены;
            $value = Helper::toFloat($price->ЦенаЗаЕдиницу) / (float) $rate;

            /**
             * Filters the price value obtained from the data.
             *
             * It can be useful if you need to do, for example, reduce / increase the price for a certain price type,
             * but do not do it on the 1C side.
             *
             * @since 1.58.1
             *
             * @param float  $value
             * @param string $priceType
             */
            $value = \apply_filters('itglx_wc1c_parsed_offer_price_value', $value, $priceType);

            $allPrices[$priceType] = (float) $value;
        }

        $salePriceType = SettingsHelper::get('price_type_2', '');

        /**
         * Filters the data set by the prices received from the offer.
         *
         * It can be used, for example, to change prices or remove some from the general set.
         *
         * @since 1.116.0
         *
         * @param array             $resolvePrices
         * @param \SimpleXMLElement $element
         */
        return \apply_filters(
            'itglx/wc1c/catalog/import/offer/resolved-prices',
            [
                'regular' => isset($allPrices[$basePriceType]) ? $allPrices[$basePriceType] : '',
                'sale' => !empty($salePriceType) && isset($allPrices[$salePriceType]) ? $allPrices[$salePriceType] : '',
                'all' => $allPrices,
            ],
            $element
        );
    }

    /**
     * The method allows to determine whether the offer contains data on price.
     *
     * @param \SimpleXMLElement $element Node `Предложение` object
     *
     * @return bool
     */
    public static function offerHasPriceData(\SimpleXMLElement $element)
    {
        return isset($element->Цены);
    }

    /**
     * @param \SimpleXMLElement $element
     * @param int               $productID
     * @param null|int          $variationID Default: null.
     *
     * @return bool If not enabled or has changed - false, if no changes - true.
     */
    public static function changeControl(\SimpleXMLElement $element, $productID, $variationID = null)
    {
        if (SettingsHelper::isEmpty('prices_change_control')) {
            return false;
        }

        $priceHash = md5(json_encode((array) $element->Цены));
        $lastPriceHash = get_post_meta($variationID ? $variationID : $productID, '_md5_offer_price', true);

        // if no changes
        if ($priceHash == $lastPriceHash) {
            return true;
        }

        if ($variationID) {
            ProductVariation::saveMetaValue($variationID, '_md5_offer_price', $priceHash, $productID);
        } else {
            Product::saveMetaValue($productID, '_md5_offer_price', $priceHash);
        }

        return false;
    }

    /**
     * Checking whether the processing of prices is disabled in the settings.
     *
     * @return bool
     */
    public static function isDisabled(): bool
    {
        return !SettingsHelper::isEmpty('skip_product_prices');
    }

    /**
     * @param int    $productOrVariationId
     * @param float  $value
     * @param string $rule
     */
    protected static function actionAfterSetPrices($productOrVariationId, $value, $rule)
    {
        /**
         * Fires after parsing and writing prices to the product / variation from the each offer with price data.
         *
         * It can be useful if you need to do some additional actions with price data, for example, additionally
         * update information in some fields.
         *
         * @since 1.16.2
         *
         * @param int    $productOrVariationId
         * @param float  $value
         * @param string $rule                 The price work rule that was used.
         */
        \do_action('itglx_wc1c_after_set_product_price', $productOrVariationId, $value, $rule);
    }
}
