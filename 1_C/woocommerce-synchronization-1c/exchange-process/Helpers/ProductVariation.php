<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer\VariationOfferAttributes;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer\VariationOfferImages;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\VariationCharacteristicsToGlobalProductAttributes;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ImageEntity;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class ProductVariation
{
    /**
     * @param \SimpleXMLElement $element
     * @param array             $variationEntry
     * @param bool              $forceUseCharacteristics Default: false.
     * @param bool              $ignoreImage             Default: false.
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function mainData($element, $variationEntry, $forceUseCharacteristics = false, $ignoreImage = false)
    {
        $isNewVariation = empty($variationEntry['ID']);
        $offerHash = md5(json_encode((array) $element));

        if (!isset($_SESSION['IMPORT_1C']['setTerms'])) {
            $_SESSION['IMPORT_1C']['setTerms'] = [];
        }

        if (!isset($_SESSION['IMPORT_1C']['productVariations'])) {
            $_SESSION['IMPORT_1C']['productVariations'] = [];
        }

        /**
         * Don't overwrite data if there is no change.
         */
        if (
            !$isNewVariation
            && SettingsHelper::isEmpty('force_update_product')
            && $offerHash == get_post_meta($variationEntry['ID'], '_md5_offer', true)
        ) {
            /**
             * @see VariationOfferAttributes::processCharacteristics()
             * @see VariationOfferAttributes::processOptions()
             */
            $currentAttributeValues = get_post_meta($variationEntry['ID'], '_itglx_wc1c_attributes_state', true);

            foreach ($currentAttributeValues as $attributeTax => $optionTermID) {
                $_SESSION['IMPORT_1C']['setTerms'][$variationEntry['post_parent']][$attributeTax][] = $optionTermID;
            }

            $_SESSION['IMPORT_1C']['productVariations'][$variationEntry['post_parent']][] = $variationEntry['ID'];

            if (
                !$ignoreImage
                && !SettingsHelper::isEmpty('more_check_image_changed')
                && SettingsHelper::isEmpty('skip_post_images')
            ) {
                // it is necessary to check the change of images,
                // since the photo can be changed without changing the file name,
                // which means the hash matches
                VariationOfferImages::process($element, $variationEntry['ID'], $variationEntry['post_parent']);
            }

            Logger::log(
                '(variation) not changed - skip, ID - '
                . $variationEntry['ID']
                . ', parent ID - '
                . $variationEntry['post_parent'],
                [(string) $element->Ид]
            );

            return $variationEntry;
        }

        // prepare of the main product and indication of its type
        if (!get_post_meta($variationEntry['post_parent'], '_is_set_variable', true)) {
            Term::setObjectTerms($variationEntry['post_parent'], 'variable', 'product_type');
            Product::saveMetaValue($variationEntry['post_parent'], '_manage_stock', 'no');
            Product::saveMetaValue($variationEntry['post_parent'], '_is_set_variable', true);
        }

        $variationEntry = self::createUpdate($element, $variationEntry, $isNewVariation);

        $_SESSION['IMPORT_1C']['productVariations'][$variationEntry['post_parent']][] = $variationEntry['ID'];

        /**
         * Filters the sign to force the use of characteristics.
         *
         * @since 1.107.0
         *
         * @param bool              $forceUseCharacteristics
         * @param \SimpleXMLElement $element                 Xml node object.
         */
        $forceUseCharacteristics = \apply_filters(
            'itglx/wc1c/catalog/import/offer/variation/force-use-characteristics',
            $forceUseCharacteristics,
            $element
        );

        if (!$forceUseCharacteristics && VariationOfferAttributes::hasOptions($element)) {
            VariationOfferAttributes::processOptions($element, $variationEntry);
        }
        // simple variant without ids
        elseif (VariationOfferAttributes::hasCharacteristics($element)) {
            VariationCharacteristicsToGlobalProductAttributes::process($element);
            VariationOfferAttributes::processCharacteristics($element, $variationEntry);
        }

        self::saveMetaValue($variationEntry['ID'], '_md5_offer', $offerHash, $variationEntry['post_parent']);

        // variation image processing
        if (
            !$ignoreImage
            && ($isNewVariation || SettingsHelper::isEmpty('skip_post_images'))
        ) {
            VariationOfferImages::process($element, $variationEntry['ID'], $variationEntry['post_parent']);
        }

        return $variationEntry;
    }

    /**
     * Create/update product variation post main data by offer data.
     *
     * @param \SimpleXMLElement $element
     * @param array             $variationEntry
     * @param bool              $isNewVariation
     *
     * @return array
     */
    public static function createUpdate($element, $variationEntry, $isNewVariation)
    {
        global $wpdb;

        // update variation
        if (!$isNewVariation) {
            /**
             * Filters the set of values for the product variation being updated.
             *
             * @since 1.93.0
             *
             * @param array             $params  Array a set of values for the product variation post.
             * @param \SimpleXMLElement $element 'Предложение' (or `Товар` for old format {@see resolveOldVariant()}) node object.
             */
            $params = apply_filters(
                'itglx_wc1c_update_post_variation_params',
                [
                    'post_title' => (string) $element->Наименование,
                    'post_parent' => $variationEntry['post_parent'],
                ],
                $element
            );

            $wpdb->update($wpdb->posts, $params, ['ID' => $variationEntry['ID']]);

            Logger::log(
                '(variation) Updated, ID - '
                . $variationEntry['ID']
                . ', parent ID - '
                . $variationEntry['post_parent'],
                [(string) $element->Ид]
            );
        }
        // create variation
        else {
            /**
             * Filters the set of values for the product variation being created.
             *
             * @since 1.93.0
             *
             * @param array             $params  Array a set of values for the product variation post.
             * @param \SimpleXMLElement $element 'Предложение' (or `Товар` for old format {@see resolveOldVariant()}) node object.
             */
            $params = apply_filters(
                'itglx_wc1c_insert_post_variation_params',
                [
                    'post_title' => (string) $element->Наименование,
                    'post_type' => 'product_variation',
                    'post_name' => uniqid(),
                    'post_parent' => $variationEntry['post_parent'],
                    /**
                     * The variation is created in the off state and the decision on its state is made when
                     * processing the stock.
                     */
                    'post_status' => 'private',
                ],
                $element
            );

            /**
             * @see https://developer.wordpress.org/reference/functions/wp_insert_post/
             */
            $variationEntry['ID'] = \wp_insert_post($params);

            self::saveMetaValue($variationEntry['ID'], '_id_1c', (string) $element->Ид, $variationEntry['post_parent']);

            Logger::log(
                '(variation) Added, ID - '
                . $variationEntry['ID']
                . ', parent ID - '
                . $variationEntry['post_parent'],
                [(string) $element->Ид]
            );

            // clear children cache
            \delete_transient('wc_product_children_' . $variationEntry['post_parent']);
        }

        // processing and recording the sku for variable offers.
        if (isset($element->Артикул)) {
            $parentSku = get_post_meta($variationEntry['post_parent'], '_sku', true);
            $offerSku = trim((string) $element->Артикул);

            if ($offerSku !== $parentSku) {
                self::saveMetaValue($variationEntry['ID'], '_sku', $offerSku, $variationEntry['post_parent']);
            }
        }

        return $variationEntry;
    }

    /**
     * The method allows to create a product variation according to the old format.
     *
     * In this case, the main data, as well as characteristics, come in node `Товар`.
     *
     * @param \SimpleXMLElement $element
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function resolveOldVariant($element)
    {
        $parseID = explode('#', (string) $element->Ид);

        // empty variation hash
        if (empty($parseID[1])) {
            return;
        }

        if (!VariationOfferAttributes::hasCharacteristics($element)) {
            return;
        }

        $variationEntry = [
            'post_parent' => Product::getProductIdByMeta($parseID[0]),
        ];

        if (empty($variationEntry['post_parent'])) {
            Logger::log('(variation) not exists parent product', [(string) $element->Ид], 'warning');

            return;
        }

        $variationEntry['ID'] = self::getIdByMeta((string) $element->Ид, '_id_1c');

        self::mainData($element, $variationEntry, true, true);
    }

    /**
     * @param $variationID
     * @param $metaKey
     * @param $metaValue
     * @param $parentProductID
     *
     * @return void
     */
    public static function saveMetaValue($variationID, $metaKey, $metaValue, $parentProductID)
    {
        \update_metadata(
            'post',
            $variationID,
            $metaKey,
            apply_filters('itglx_wc1c_variation_meta_' . $metaKey . '_value', $metaValue, $variationID, $parentProductID)
        );
    }

    /**
     * The method allows to find the post id of the variation by the meta key and value.
     *
     * @param string $value
     * @param string $metaKey
     *
     * @return null|int
     *
     * @see https://developer.wordpress.org/reference/classes/wpdb/
     */
    public static function getIdByMeta($value, $metaKey = '_id_1c')
    {
        global $wpdb;

        $product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT `meta`.`post_id` as `post_id`, `posts`.`post_type` as `post_type` FROM `{$wpdb->postmeta}` as `meta`
                INNER JOIN `{$wpdb->posts}` as `posts` ON (`meta`.`post_id` = `posts`.`ID`)
                WHERE `posts`.`post_type` = 'product_variation' AND `meta`.`meta_value` = %s AND `meta`.`meta_key` = %s",
                (string) $value,
                (string) $metaKey
            )
        );

        if (!isset($product->post_type)) {
            return null;
        }

        return (int) $product->post_id;
    }

    /**
     * Deleting a product variation image.
     *
     * @param int $variationID
     *
     * @return void
     */
    public static function removeImage($variationID)
    {
        if (!SettingsHelper::isEmpty('images_not_delete_related_when_delete_variation')) {
            Logger::log('(image variation) `images_not_delete_related_when_delete_variation` is active, ID - ' . $variationID);

            return;
        }

        if (!\get_post_meta($variationID, '_thumbnail_id', true)) {
            return;
        }

        ImageEntity::remove(\get_post_meta($variationID, '_thumbnail_id', true), $variationID);
        \delete_post_meta($variationID, '_thumbnail_id');
    }

    /**
     * Deleting a product variation.
     *
     * @param int $variationID
     * @param int $productID
     *
     * @return void
     *
     * @see https://developer.wordpress.org/reference/functions/wp_delete_post/
     */
    public static function remove($variationID, $productID = 0)
    {
        self::removeImage($variationID);

        Logger::log(
            '(variation) removed variation, ID - ' . $variationID,
            [get_post_meta($variationID, '_id_1c', true)]
        );

        wp_delete_post($variationID, true);

        if ($productID) {
            // clear children cache
            \delete_transient('wc_product_children_' . $productID);
        }
    }

    /**
     * Enable a product variation.
     *
     * @param int $variationID
     *
     * @return void
     */
    public static function enable($variationID)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->posts,
            [
                'post_status' => 'publish',
            ],
            [
                'ID' => $variationID,
            ]
        );
    }

    /**
     * Disable a product variation.
     *
     * @param int $variationID
     *
     * @return void
     */
    public static function disable($variationID)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->posts,
            [
                'post_status' => 'private',
            ],
            [
                'ID' => $variationID,
            ]
        );
    }
}
