<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\NodeAsProductAttribute;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeValueEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Parsing and saving data on the manufacturer of a specific product.
 *
 * Example xml structure (position - Товар -> Изготовитель)
 *
 * ```xml
 * <Изготовитель>
 *      <Ид>404fc2e6-cd9d-11e6-8b9d-60eb696dc507</Ид>
 *      <Наименование>Наименование изготовителя</Наименование>
 * </Изготовитель>
 */
class ManufacturerProduct extends NodeAsProductAttribute
{
    public static $nameOptionDisabled = 'skip_product_manufacturer';

    /**
     * @param \SimpleXMLElement $element
     * @param int               $productId
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function process($element, $productId)
    {
        if (
            self::isDisabled()
            || !isset($element->Изготовитель)
            || !isset($element->Изготовитель->Ид)
        ) {
            return;
        }

        $taxName = self::resolveAttribute();

        if (empty($taxName)) {
            return;
        }

        $uniqueId1c = md5(
            (string) $element->Изготовитель->Ид
            . $_SESSION['IMPORT_1C']['brand_taxonomy']['createdTaxName']
        );

        if (
            !isset($_SESSION['IMPORT_1C']['brand_taxonomy']['values'])
            || !isset($_SESSION['IMPORT_1C']['brand_taxonomy']['values'][$uniqueId1c])
        ) {
            $optionTermID = self::resolveValue($element, $uniqueId1c, $taxName);
        } else {
            $optionTermID = $_SESSION['IMPORT_1C']['brand_taxonomy']['values'][$uniqueId1c];
        }

        if ($optionTermID) {
            self::saveValueToProduct($productId, $taxName, $optionTermID);
        }
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string            $uniqueId1c
     * @param string            $taxName
     *
     * @return int ID of the created / updated term.
     *
     * @throws \Exception
     */
    private static function resolveValue($element, $uniqueId1c, $taxName)
    {
        if (!isset($_SESSION['IMPORT_1C']['brand_taxonomy']['values'])) {
            $_SESSION['IMPORT_1C']['brand_taxonomy']['values'] = [];
        }

        $optionTermID = Term::getTermIdByMeta($uniqueId1c);
        $manufacturerName = (string) $element->Изготовитель->Наименование;

        /*
         * If the value is not found and the taxonomy is not an attribute of the product,
         * then we will try to find it by name
         */
        if (!$optionTermID && !self::isProductAttribute($taxName)) {
            $term = \get_term_by('name', \wp_slash($manufacturerName), $taxName);

            if ($term) {
                $optionTermID = $term->term_id;

                Term::update1cId($optionTermID, $uniqueId1c);
            }
        }

        // if exists when update
        if ($optionTermID) {
            ProductAttributeValueEntity::update($manufacturerName, $optionTermID, $taxName);
        } else {
            $optionTermID = ProductAttributeValueEntity::insert($manufacturerName, $taxName, $uniqueId1c);
            $optionTermID = $optionTermID['term_id'];

            // default meta value by ordering
            \update_term_meta($optionTermID, 'order_' . $taxName, 0);

            Term::update1cId($optionTermID, $uniqueId1c);
        }

        if ($optionTermID) {
            $_SESSION['IMPORT_1C']['brand_taxonomy']['values'][$uniqueId1c] = $optionTermID;
        }

        return $optionTermID;
    }

    /**
     * @return string Taxonomy name.
     *
     * @throws \Exception
     */
    private static function resolveAttribute()
    {
        if (isset($_SESSION['IMPORT_1C']['brand_taxonomy'])) {
            return $_SESSION['IMPORT_1C']['brand_taxonomy']['name'];
        }

        $_SESSION['IMPORT_1C']['brand_taxonomy'] = [];

        /**
         * Filters and allows overriding, the taxonomy for the manufacturer.
         *
         * @since 1.102.0
         *
         * @param string $manufacturerTaxonomy Default: ''.
         */
        $manufacturerTaxonomy = \apply_filters('itglx/wc1c/catalog/import/product-manufacturer-taxonomy', '');

        if (!empty($manufacturerTaxonomy)) {
            $_SESSION['IMPORT_1C']['brand_taxonomy']['name'] = $manufacturerTaxonomy;
            $_SESSION['IMPORT_1C']['brand_taxonomy']['createdTaxName'] = $manufacturerTaxonomy;

            Logger::log('[manufacturer] taxonomy redefined', [$manufacturerTaxonomy]);

            return $manufacturerTaxonomy;
        }

        return self::getTaxonomyName('Изготовитель', 'brand_', 'brand_taxonomy');
    }
}
