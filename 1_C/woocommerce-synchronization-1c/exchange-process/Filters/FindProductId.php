<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FindProductId
{
    private static $instance = false;

    private function __construct()
    {
        if (!SettingsHelper::isEmpty('find_product_by_sku')) {
            \add_filter('itglx_wc1c_find_product_id', [$this, 'findBySku'], 10, 2);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function findBySku($productId, $element)
    {
        if ((int) $productId) {
            return $productId;
        }

        if (empty($element->Артикул)) {
            return $productId;
        }

        /**
         * The product must not have `_id_1c` or it must be empty, and it must also have exactly the same SKU.
         *
         * @see https://developer.wordpress.org/reference/functions/get_posts/
         */
        $product = \get_posts(
            [
                'post_type' => 'product',
                'post_status' => 'any',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'relation' => 'OR',
                        [
                            'key' => '_id_1c',
                            'value' => '',
                        ],
                        [
                            'key' => '_id_1c',
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                    [
                        'key' => '_sku',
                        'value' => trim((string) $element->Артикул),
                    ],
                ],
            ]
        );

        if (\is_wp_error($product) || empty($product)) {
            return $productId;
        }

        return $product[0];
    }
}
