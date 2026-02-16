<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class CategoriesProduct
{
    /**
     * Main logic.
     *
     * Example xml structure (position - Товар -> Группы)
     *
     * ```xml
     * <Группы>
     *     <Ид>6d615f3c-4266-11e4-ae62-1c6f65cec896</Ид>
     *     <Ид>8de28a6b-1903-11e2-bc2c-10bf4876822f</Ид>
     * </Группы>
     *
     * @param \SimpleXMLElement $element   Node object "Товар".
     * @param int               $productID
     *
     * @return array
     */
    public static function process(\SimpleXMLElement $element, $productID)
    {
        if (!isset($element->Группы->Ид)) {
            return [];
        }

        $categoryIds = self::getCategoryList();

        if (empty($categoryIds)) {
            return [];
        }

        $resolvedList = [];

        foreach ($element->Группы->Ид as $groupXmlId) {
            if (!isset($categoryIds[(string) $groupXmlId])) {
                continue;
            }

            $resolvedList[] = $categoryIds[(string) $groupXmlId];
        }

        return self::setCategoryListToProduct($productID, array_unique($resolvedList));
    }

    /**
     * @return array
     */
    private static function getCategoryList()
    {
        if (!isset($_SESSION['IMPORT_1C']['categoryIds'])) {
            $_SESSION['IMPORT_1C']['categoryIds'] = !self::isDisabled() ? Term::getProductCatIDs() : [];
        }

        return $_SESSION['IMPORT_1C']['categoryIds'];
    }

    /**
     * @param int   $productID
     * @param array $categoryIds
     *
     * @return array
     *
     * @see https://developer.wordpress.org/reference/functions/wp_get_object_terms/
     */
    private static function setCategoryListToProduct($productID, $categoryIds)
    {
        if (empty($categoryIds)) {
            Logger::log(
                '(product) empty `product_cat` list, ID - ' . $productID,
                [get_post_meta($productID, '_id_1c', true)]
            );

            return [];
        }

        if (empty($_SESSION['IMPORT_1C']['product_cat_list'])) {
            $_SESSION['IMPORT_1C']['product_cat_list'] = Term::getProductCatIDs(false);
        }

        $currentProductCats = wp_get_object_terms($productID, 'product_cat', ['fields' => 'ids']);

        // add only categories not from 1C to the main set
        if (!empty($currentProductCats)) {
            foreach ($currentProductCats as $termID) {
                if (!in_array($termID, $_SESSION['IMPORT_1C']['product_cat_list'])) {
                    $categoryIds[] = $termID;
                }
            }
        }

        $categoryIds = array_map('intval', $categoryIds);

        Logger::log(
            '(product) set `product_cat` list, ID - ' . $productID,
            [get_post_meta($productID, '_id_1c', true), $categoryIds]
        );

        Term::setObjectTerms($productID, $categoryIds, 'product_cat');

        return $categoryIds;
    }

    /**
     * Checking whether the processing categories is disabled in the settings.
     *
     * @return bool
     */
    private static function isDisabled()
    {
        return !SettingsHelper::isEmpty('skip_categories');
    }
}
