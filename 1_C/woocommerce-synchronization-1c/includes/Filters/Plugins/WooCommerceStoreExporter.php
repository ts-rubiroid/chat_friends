<?php

namespace Itgalaxy\Wc\Exchange1c\Includes\Filters\Plugins;

/**
 * @see https://wordpress.org/plugins/woocommerce-exporter/
 */
class WooCommerceStoreExporter
{
    private static $instance = false;

    private function __construct()
    {
        \add_filter('woo_ce_product_fields', [$this, 'addPriceFields']);
        \add_filter('woo_ce_product_item', [$this, 'fillPriceFields'], 10, 2);
        \add_filter('woo_ce_product_fields', [$this, 'addStoreFields']);
        \add_filter('woo_ce_product_item', [$this, 'fillStoreFields'], 10, 2);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function addPriceFields($fields)
    {
        $allPriceTypes = \get_option('all_prices_types', []);

        foreach ($allPriceTypes as $guid => $priceType) {
            $fields[] = [
                'name' => 'price_type_1c_' . $guid,
                'label' => 'Price type - ' . $priceType['name'] . ' (' . $guid . ')',
            ];
        }

        return $fields;
    }

    /**
     * @param \stdClass $product
     * @param int       $productId
     *
     * @return \stdClass
     */
    public function fillPriceFields($product, $productId)
    {
        $allProductPrices = \get_post_meta($productId, '_all_prices', true);
        $allPriceTypes = \get_option('all_prices_types', []);

        foreach ($allPriceTypes as $guid => $_) {
            $product->{'price_type_1c_' . $guid} = isset($allProductPrices[$guid]) ? $allProductPrices[$guid] : '';
        }

        return $product;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function addStoreFields($fields)
    {
        $stocks = \get_option('all_1c_stocks', []);

        foreach ($stocks as $guid => $warehouse) {
            $fields[] = [
                'name' => 'warehouse_1c_' . $guid,
                'label' => 'Stock - ' . $warehouse['Наименование'] . ' (' . $guid . ')',
            ];
        }

        return $fields;
    }

    /**
     * @param \stdClass $product
     * @param int       $productId
     *
     * @return \stdClass
     */
    public function fillStoreFields($product, $productId)
    {
        $productStockData = \get_post_meta($productId, '_separate_warehouse_stock', true);
        $stocks = \get_option('all_1c_stocks', []);

        foreach ($stocks as $guid => $_) {
            $product->{'warehouse_1c_' . $guid} = isset($productStockData[$guid]) ? $productStockData[$guid] : 0;
        }

        return $product;
    }
}
