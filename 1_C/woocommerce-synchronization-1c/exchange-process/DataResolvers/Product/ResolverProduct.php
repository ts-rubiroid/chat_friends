<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class ResolverProduct
{
    /**
     * @param \SimpleXMLElement $element 'Товар' node object.
     *
     * @return bool
     */
    public static function customProcessing(\SimpleXMLElement $element)
    {
        if (!\has_action('itglx_wc1c_product_custom_processing')) {
            return false;
        }

        Logger::log('(product) has_action `itglx_wc1c_product_custom_processing` - run', [(string) $element->Ид]);

        /**
         * The action allows to organize custom processing of the product.
         *
         * If an action is registered, then it is triggered for every product.
         *
         * @since 1.95.0
         *
         * @param \SimpleXMLElement $element 'Товар' node object.
         */
        \do_action('itglx_wc1c_product_custom_processing', $element);

        return true;
    }

    /**
     * @param \SimpleXMLElement $element 'Товар' node object.
     *
     * @return bool
     */
    public static function skipByXml(\SimpleXMLElement $element)
    {
        /**
         * Filters the sign to ignore the processing of the node `Товар`.
         *
         * @since 1.19.0
         *
         * @param bool              $ignore
         * @param \SimpleXMLElement $element 'Товар' node object
         */
        return \apply_filters('itglx_wc1c_skip_product_by_xml', false, $element);
    }

    /**
     * Checking if the reader is in the position of data on product.
     *
     * @param \XMLReader $reader
     *
     * @return bool
     */
    public static function isProductNode(\XMLReader $reader)
    {
        return $reader->name === 'Товар' && $reader->nodeType === \XMLReader::ELEMENT;
    }

    /**
     * @param \SimpleXMLElement $element 'Товар' node object.
     *
     * @return bool
     */
    public static function isOfferAsProduct(\SimpleXMLElement $element)
    {
        // example: dffb9584-e608-11eb-9389-0019d2f82540#92a8676f-8b35-11e8-a8af-001d72037c87
        $explodedGuid = explode('#', (string) $element->Ид);

        return !empty($explodedGuid[1]);
    }

    /**
     * @param \SimpleXMLElement $element       'Товар' node object.
     * @param int               $product
     * @param int[]             $all1cProducts
     *
     * @return bool
     */
    public static function isRemoved(\SimpleXMLElement $element, $product, $all1cProducts)
    {
        /**
         * Filters the sign when an product is considered deleted.
         *
         * @since 1.61.1
         *
         * @param bool              $isRemoved
         * @param \SimpleXMLElement $element
         * @param int               $product
         *
         * @see ProductIsRemoved
         */
        $productIsRemoved = \apply_filters('itglx_wc1c_product_is_removed', false, $element, $product);

        if (!$productIsRemoved) {
            return false;
        }

        Logger::log('(product) is marked for deletion', [$product, (string) $element->Ид]);

        if (empty($product)) {
            return true;
        }

        // product completely removed
        if (SettingsHelper::isEmpty('remove_marked_products_to_trash')) {
            Product::removeProduct($product);
        }
        // to trash
        else {
            Product::removeProduct($product, true);

            $_SESSION['IMPORT_1C_PROCESS']['allCurrentProducts'][] = $product;
            $all1cProducts[] = $product;

            \update_option('all1cProducts', array_unique($all1cProducts));
        }

        return true;
    }

    /**
     * @param \SimpleXMLElement $element 'Товар' node object.
     *
     * @return string
     */
    public static function getNomenclatureGuid(\SimpleXMLElement $element)
    {
        $explodedGuid = explode('#', (string) $element->Ид);

        /** We should only use the first part before #, as the product may be {@see isOfferAsProduct} */
        return $explodedGuid[0];
    }
}
