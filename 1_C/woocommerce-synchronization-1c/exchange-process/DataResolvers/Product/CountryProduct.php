<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\NodeAsProductAttribute;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeValueEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Parsing and saving data on the country of a specific product.
 *
 * Example xml structure (position - Товар -> Страна)
 *
 * ```xml
 * <Страна>Россия</Страна>
 */
class CountryProduct extends NodeAsProductAttribute
{
    public static $nameOptionDisabled = 'skip_product_country';

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
            || !isset($element->Страна)
            || empty((string) $element->Страна)
        ) {
            return;
        }

        $taxName = self::resolveAttribute();

        if (empty($taxName)) {
            return;
        }

        $uniqueId1c = md5(
            mb_strtolower((string) $element->Страна)
            . $_SESSION['IMPORT_1C']['country_taxonomy']['createdTaxName']
        );

        if (
            !isset($_SESSION['IMPORT_1C']['country_taxonomy']['values'])
            || !isset($_SESSION['IMPORT_1C']['country_taxonomy']['values'][$uniqueId1c])
        ) {
            $optionTermID = self::resolveValue($element, $uniqueId1c, $taxName);
        } else {
            $optionTermID = $_SESSION['IMPORT_1C']['country_taxonomy']['values'][$uniqueId1c];
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
        if (!isset($_SESSION['IMPORT_1C']['country_taxonomy']['values'])) {
            $_SESSION['IMPORT_1C']['country_taxonomy']['values'] = [];
        }

        $optionTermID = Term::getTermIdByMeta($uniqueId1c);

        if (!$optionTermID) {
            $term = \get_term_by('name', \wp_slash((string) $element->Страна), $taxName);

            if ($term) {
                $optionTermID = $term->term_id;

                Term::update1cId($optionTermID, $uniqueId1c);
            }
        }

        if (!$optionTermID) {
            $optionTermID = ProductAttributeValueEntity::insert((string) $element->Страна, $taxName, $uniqueId1c);
            $optionTermID = $optionTermID['term_id'];

            // default meta value by ordering
            \update_term_meta($optionTermID, 'order_' . $taxName, 0);

            Term::update1cId($optionTermID, $uniqueId1c);
        }

        if ($optionTermID) {
            $_SESSION['IMPORT_1C']['country_taxonomy']['values'][$uniqueId1c] = $optionTermID;
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
        if (isset($_SESSION['IMPORT_1C']['country_taxonomy'])) {
            return $_SESSION['IMPORT_1C']['country_taxonomy']['name'];
        }

        $_SESSION['IMPORT_1C']['country_taxonomy'] = [];

        /**
         * Filters and allows overriding, the taxonomy for the country.
         *
         * @since 1.103.0
         *
         * @param string $countryTaxonomy Default: ''.
         */
        $countryTaxonomy = \apply_filters('itglx/wc1c/catalog/import/product-country-taxonomy', '');

        if (!empty($countryTaxonomy)) {
            $_SESSION['IMPORT_1C']['country_taxonomy']['name'] = $countryTaxonomy;
            $_SESSION['IMPORT_1C']['country_taxonomy']['createdTaxName'] = $countryTaxonomy;

            Logger::log('[country] taxonomy redefined', [$countryTaxonomy]);

            return $countryTaxonomy;
        }

        return self::getTaxonomyName('Страна', 'country_', 'country_taxonomy');
    }
}
