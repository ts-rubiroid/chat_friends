<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Class NodeAsProductAttribute.
 */
abstract class NodeAsProductAttribute
{
    /**
     * @var string
     */
    public static $nameOptionDisabled;

    /**
     * Checking whether the processing of node is disabled in the settings.
     *
     * @return bool
     */
    public static function isDisabled(): bool
    {
        return !SettingsHelper::isEmpty(static::$nameOptionDisabled);
    }

    /**
     * @param string $name
     * @param string $prefix
     * @param string $sessionKey
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getTaxonomyName(string $name, string $prefix, string $sessionKey): string
    {
        $attributeName = $prefix . hash('crc32', $name);
        $attributeTaxName = 'pa_' . $attributeName;

        $attribute = ProductAttributeEntity::get($attributeTaxName);

        // exists
        if ($attribute) {
            $_SESSION['IMPORT_1C'][$sessionKey]['name'] = 'pa_' . $attribute->attribute_name;
            $_SESSION['IMPORT_1C'][$sessionKey]['createdTaxName'] = $attributeTaxName;

            return 'pa_' . $attribute->attribute_name;
        }

        $attributeCreate = ProductAttributeEntity::insert($name, $attributeName, $attributeTaxName);
        Logger::log('(attribute) created attribute `' . $name . '`', $attributeCreate);
        $_SESSION['IMPORT_1C'][$sessionKey]['createdTaxName'] = $attributeTaxName;

        $attributeTaxName = 'pa_' . $attributeCreate['attribute_name'];

        $_SESSION['IMPORT_1C'][$sessionKey]['name'] = $attributeTaxName;

        /**
         * To use the taxonomy right after the attribute is created, we need to register it. The next time
         * the site is loaded, the taxonomy will already be registered with the WooCommerce logic,
         * so we only need it now.
         *
         * @see https://developer.wordpress.org/reference/functions/register_taxonomy/
         */
        \register_taxonomy($attributeTaxName, null);

        return $attributeTaxName;
    }

    /**
     * @param int    $productId
     * @param string $taxName
     * @param int    $termId
     *
     * @return void
     */
    public static function saveValueToProduct(int $productId, string $taxName, int $termId): void
    {
        /*
        * If the taxonomy is an attribute of the product, then it is necessary to write information into
        * the attributes of the product.
        */
        if (self::isProductAttribute($taxName)) {
            $productAttributes = \get_post_meta($productId, '_product_attributes', true);

            if (empty($productAttributes)) {
                $productAttributes = [];
            }

            if (!isset($productAttributes[$taxName])) {
                $productAttributes[$taxName] = [
                    'name' => \wc_clean($taxName),
                    'value' => '',
                    'position' => 0,
                    'is_visible' => 1,
                    'is_variation' => 0,
                    'is_taxonomy' => 1,
                ];

                Product::saveMetaValue($productId, '_product_attributes', $productAttributes);
            }
        }

        Term::setObjectTerms($productId, $termId, $taxName);
    }

    /**
     * @param string $taxonomy
     *
     * @return bool
     */
    public static function isProductAttribute(string $taxonomy): bool
    {
        // If it is an attribute, then the taxonomy is always prefixed `pa_`
        return strpos($taxonomy, 'pa_') === 0;
    }
}
