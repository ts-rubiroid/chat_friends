<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SetVariationAttributeToProducts
{
    /**
     * @throws ProgressException
     */
    public static function process()
    {
        if (empty($_SESSION['IMPORT_1C']['setTerms'])) {
            return;
        }

        foreach ($_SESSION['IMPORT_1C']['setTerms'] as $productID => $tax) {
            if (HeartBeat::limitIsExceeded()) {
                Logger::log('SetVariationAttributeToProducts - progress');

                throw new ProgressException('applying variable attributes to products...');
            }

            $productAttributes = get_post_meta($productID, '_product_attributes', true);

            if (!is_array($productAttributes)) {
                $productAttributes = [];
            }

            $allCurrentVariableTaxes = self::setAttributes($productAttributes, $tax, $productID);

            // remove non exists variation attributes
            $resolvedAttributes = self::removeNonExistsVariationAttributes(
                $productAttributes,
                $allCurrentVariableTaxes,
                $productID
            );

            // clean up missing product variations
            if (
                !SettingsHelper::isEmpty('remove_missing_variation')
                && !empty($_SESSION['IMPORT_1C']['productVariations'])
                && !empty($_SESSION['IMPORT_1C']['productVariations'][$productID])
            ) {
                self::cleanupMissingProductVariations($productID);
            }

            Product::saveMetaValue($productID, '_product_attributes', $resolvedAttributes);

            unset($_SESSION['IMPORT_1C']['setTerms'][$productID]);
        }

        unset($_SESSION['IMPORT_1C']['setTerms']);
    }

    private static function setAttributes(&$productAttributes, $taxesInfo, $productID)
    {
        $allCurrentVariableTaxes = [];

        foreach ($taxesInfo as $taxonomy => $ids) {
            $allCurrentVariableTaxes[] = $taxonomy;

            // skip updating data on variable attributes if disabled and attributes are already configured
            if (
                isset($productAttributes[$taxonomy])
                && !SettingsHelper::isEmpty('skip_update_set_attribute_for_variations')
            ) {
                Logger::log(
                    '(product) update set variation attributes skip as is enabled - '
                    . 'skip_update_set_attribute_for_variations, ID - '
                    . $productID,
                    [get_post_meta($productID, '_id_1c', true), $taxonomy]
                );
            } else {
                /**
                 * Filters a set of parameters when writing information about a variable attribute to a product.
                 *
                 * @since 1.80.1
                 *
                 * @param array $variationAttributeArgs
                 */
                $productAttributes[$taxonomy] = \apply_filters(
                    'itglx_wc1c_set_product_variation_attribute_args',
                    [
                        'name' => \wc_clean($taxonomy),
                        'value' => '',
                        'position' => 0,
                        'is_visible' => SettingsHelper::isEmpty('attribute_variable_enable_visibility') ? 0 : 1,
                        'is_variation' => 1,
                        'is_taxonomy' => 1,
                    ]
                );

                Logger::log(
                    '(product) Set variation attribute, ID - ' . $productID,
                    [get_post_meta($productID, '_id_1c', true), $taxonomy]
                );
            }

            $ids = array_map('intval', $ids);
            $ids = array_unique($ids);

            /**
             * Filters the parameter `append` when setting links with values in variable attribute.
             *
             * If `false`, then there is a replacement for the current set, and if `true`, then the addition instead
             * of replacement.
             *
             * @since 1.88.0
             *
             * @param false $append Default: false.
             */
            $append = \apply_filters('itglx_wc1c_set_product_variation_attribute_values_append', false);

            Term::setObjectTerms($productID, $ids, $taxonomy, $append);
            Logger::log(
                '(product) Set attribute terms, ID - ' . $productID,
                [get_post_meta($productID, '_id_1c', true), array_values($ids), $taxonomy]
            );
        }

        return $allCurrentVariableTaxes;
    }

    private static function removeNonExistsVariationAttributes($productAttributes, $allCurrentTaxes, $productID)
    {
        if (!SettingsHelper::isEmpty('skip_update_set_attribute_for_variations')) {
            return $productAttributes;
        }

        $resolvedAttributes = $productAttributes;

        foreach ($productAttributes as $key => $value) {
            if (empty($key)) {
                unset($resolvedAttributes[$key]);

                continue;
            }

            if (!$value['is_variation'] || in_array($key, $allCurrentTaxes, true)) {
                continue;
            }

            unset($resolvedAttributes[$key]);

            Term::setObjectTerms($productID, [], $key);
            Logger::log(
                '(product) Unset variation attribute, ID - ' . $productID,
                [get_post_meta($productID, '_id_1c', true), $key]
            );
        }

        return $resolvedAttributes;
    }

    /**
     * @param int $productID
     *
     * @see https://developer.wordpress.org/reference/functions/wp_parse_id_list/
     *
     * @return void
     */
    private static function cleanupMissingProductVariations($productID)
    {
        $variationIds = wp_parse_id_list(
            get_posts(
                [
                    'post_parent' => $productID,
                    'post_type' => 'product_variation',
                    'fields' => 'ids',
                    'post_status' => ['any', 'trash', 'auto-draft'],
                    'numberposts' => -1,
                ]
            )
        );

        Logger::log(
            '(product) current exchange variation list, ID - ' . $productID,
            [
                get_post_meta($productID, '_id_1c', true),
                json_encode($_SESSION['IMPORT_1C']['productVariations'][$productID]),
            ]
        );

        if (empty($variationIds)) {
            Logger::log('(product) has no variations, ID - ' . $productID);
            delete_transient('wc_product_children_' . $productID);

            return;
        }

        foreach ($variationIds as $variationId) {
            if (in_array($variationId, $_SESSION['IMPORT_1C']['productVariations'][$productID])) {
                continue;
            }

            ProductVariation::remove($variationId);
        }

        delete_transient('wc_product_children_' . $productID);
    }
}
