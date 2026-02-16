<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class ProductVariablePrices
{
    /**
     * @throws ProgressException
     */
    public static function process()
    {
        global $wpdb;

        if (isset($_SESSION['IMPORT_1C']['variableProductsPrices'])) {
            return;
        }

        if (!isset($_SESSION['IMPORT_1C']['hasVariation'])) {
            return;
        }

        Logger::log('variable product prices - start');

        if (!isset($_SESSION['IMPORT_1C']['numberOfSetPrices'])) {
            $_SESSION['IMPORT_1C']['numberOfSetPrices'] = 0;
        }

        $numberOfSetPrices = 0;

        foreach ($_SESSION['IMPORT_1C']['hasVariation'] as $productID => $_) {
            if (HeartBeat::limitIsExceeded()) {
                Logger::log('variable product prices - progress');

                throw new ProgressException("variable product prices {$numberOfSetPrices}...");
            }

            ++$numberOfSetPrices;

            if ($numberOfSetPrices <= $_SESSION['IMPORT_1C']['numberOfSetPrices']) {
                continue;
            }

            if (!get_post_meta($productID, '_is_set_variable', true)) {
                Logger::log('(product) variable prices, ignore as empty `_is_set_variable` - ' . $productID);

                $_SESSION['IMPORT_1C']['numberOfSetPrices'] = $numberOfSetPrices;
                continue;
            }

            $prices = array_unique(
                $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT `meta`.`meta_value` FROM `{$wpdb->postmeta}` as `meta`
                         INNER JOIN `{$wpdb->posts}` as `posts` ON (`meta`.`post_id` = `posts`.`ID`)
                         WHERE `posts`.`post_status` = 'publish'
                         AND `posts`.`post_type` = 'product_variation'
                         AND `posts`.`post_parent` = %d
                         AND `meta`.`meta_key` = '_price' AND `meta`.`meta_value` > 0",
                        $productID
                    )
                )
            );

            $oldPriceList = get_post_meta($productID, '_old_children_price_list', true);

            if (array_diff($prices, (array) $oldPriceList)) {
                $product = \wc_get_product($productID);

                \delete_post_meta($productID, '_price');

                if ($prices) {
                    sort($prices, SORT_NUMERIC);

                    foreach ($prices as $price) {
                        \add_post_meta($productID, '_price', $price);
                    }
                }

                Product::saveMetaValue($productID, '_old_children_price_list', $prices);

                Logger::log('(product) variable update prices - ' . $productID, $prices);

                $product->save();
            } else {
                Logger::log('(product) variable no changes prices - ' . $productID, $prices);
            }

            $_SESSION['IMPORT_1C']['numberOfSetPrices'] = $numberOfSetPrices;
        }

        Logger::log('variable product prices - end');

        $_SESSION['IMPORT_1C']['variableProductsPrices'] = true;
    }
}
