<?php

namespace Itgalaxy\Wc\Exchange1c\Includes\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class WcGetPriceHtmlShowPriceListDetailProductPage
{
    public static $priceRules = ['regular_and_show_list', 'regular_and_show_list_and_apply_price_depend_cart_totals'];

    private static $instance = false;

    private function __construct()
    {
        if (SettingsHelper::isEmpty('price_work_rule')) {
            return;
        }

        $showPriceWorkRules = apply_filters('itglx_wc1c_price_work_rules_product_page_show_list', self::$priceRules);

        if (in_array(SettingsHelper::get('price_work_rule'), $showPriceWorkRules, true)) {
            // https://docs.woocommerce.com/wc-apidocs/hook-docs.html
            add_filter('woocommerce_get_price_html', [$this, 'priceHtml'], 10, 2);
            add_filter('woocommerce_available_variation', [$this, 'availableVariation']);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function priceHtml($price, $product)
    {
        if (!is_product() || !is_single(get_the_ID()) || !$product->is_type('simple')) {
            return $price;
        }

        $productPrices = get_post_meta($product->get_id(), '_all_prices', true);

        if (empty($productPrices)) {
            return $price;
        }

        $allPriceTypes = get_option('all_prices_types');

        if (empty($allPriceTypes)) {
            return $price;
        }

        $return = '';
        $countPriceTypes = (int) count($allPriceTypes);

        for ($i = 1; $i <= $countPriceTypes; ++$i) {
            if (
                !SettingsHelper::isEmpty('price_type_' . $i)
                && !empty($productPrices[SettingsHelper::get('price_type_' . $i)])
            ) {
                $return .= '<span class="product-price-list-item">'
                    . \wc_price($productPrices[SettingsHelper::get('price_type_' . $i)])
                    . (
                        !SettingsHelper::isEmpty('price_type_' . $i . '_text')
                        ? ' <small class="product-price-list-item-name">('
                        . esc_html(SettingsHelper::get('price_type_' . $i . '_text'))
                        . ')</small>'
                        : ''
                    )
                    . '</span><br>';
            }
        }

        return $return;
    }

    /**
     * @param array $variation
     *
     * @return array
     */
    public function availableVariation($variation)
    {
        $variationPrices = get_post_meta($variation['variation_id'], '_all_prices', true);

        if (empty($variationPrices)) {
            return $variation;
        }

        $allPriceTypes = get_option('all_prices_types', []);

        if (empty($allPriceTypes)) {
            return $variation;
        }

        $variation['price_html'] = '';
        $countPriceTypes = count($allPriceTypes);

        for ($i = 1; $i <= $countPriceTypes; ++$i) {
            $priceType = SettingsHelper::get('price_type_' . $i);

            if (empty($priceType) || empty($variationPrices[$priceType])) {
                continue;
            }

            $variation['price_html'] .= '<div class="variation-price-list-item">'
                . \wc_price($variationPrices[$priceType])
                . (
                    !SettingsHelper::isEmpty('price_type_' . $i . '_text')
                    ? ' <small class="variation-price-list-item-name">('
                        . esc_html(SettingsHelper::get('price_type_' . $i . '_text'))
                        . ')</small>'
                    : ''
                )
                . '</div>';
        }

        return $variation;
    }
}
