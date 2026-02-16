<?php

namespace Itgalaxy\Wc\Exchange1c\Includes\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class WcCartItemPriceShowSalePrice
{
    public static $priceRules = ['regular_and_show_list_and_apply_price_depend_cart_totals'];

    private static $instance = false;

    private function __construct()
    {
        if (SettingsHelper::isEmpty('price_work_rule')) {
            return;
        }

        $workRules = apply_filters('itglx_wc1c_price_work_rules_show_sale_price_in_cart', self::$priceRules);

        if (in_array(SettingsHelper::get('price_work_rule'), $workRules, true)) {
            // https://docs.woocommerce.com/wc-apidocs/hook-docs.html
            add_filter('woocommerce_cart_item_price', [$this, 'cartItemPriceDisplay'], 30, 2);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function cartItemPriceDisplay($price, $cartItem)
    {
        if ($cartItem['data']->is_on_sale()) {
            return $cartItem['data']->get_price_html();
        }

        return $price;
    }
}
