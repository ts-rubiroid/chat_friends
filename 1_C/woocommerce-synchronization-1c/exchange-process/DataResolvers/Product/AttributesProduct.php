<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeValueEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Parsing and saving data on the attributes of a specific product.
 */
class AttributesProduct
{
    /**
     * Parsing attributes and values for a product according to data in XML.
     *
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
            !isset($element->ЗначенияСвойств)
            || !isset($element->ЗначенияСвойств->ЗначенияСвойства)
        ) {
            $productAttributes = get_post_meta($productId, '_product_attributes', true);

            /*
             * Execution of logic is necessary to remove attributes that could have been added earlier
             * but now the product in XML does not contain attributes
             */
            if (!empty($productAttributes)) {
                self::setAttributes($productId, [], $productAttributes, []);
            }

            return;
        }

        $productOptions = get_option('all_product_options');
        $productAttributes = get_post_meta($productId, '_product_attributes', true);

        if (empty($productAttributes)) {
            $productAttributes = [];
        }

        /**
         * Filters the list of product properties to be ignored during processing.
         *
         * @since 1.74.1
         *
         * @param string[] $ignoreAttributeProcessing Array of strings with property guid to be ignored during processing.
         */
        $ignoreAttributeProcessing = \apply_filters('itglx_wc1c_attribute_ignore_guid_array', []);
        $currentAttributes = [];
        $setAttributes = [];

        foreach ($element->ЗначенияСвойств->ЗначенияСвойства as $property) {
            if (has_action('itglx_wc1c_product_option_custom_processing_' . (string) $property->Ид)) {
                /**
                 * The action allows to organize custom processing of the values of which property of the product.
                 *
                 * @since 1.86.1
                 *
                 * @param int               $productId
                 * @param \SimpleXMLElement $property
                 */
                \do_action('itglx_wc1c_product_option_custom_processing_' . (string) $property->Ид, $productId, $property);

                continue;
            }

            if (in_array((string) $property->Ид, $ignoreAttributeProcessing, true)) {
                continue;
            }

            if (
                (empty($property->Значение) && empty($property->ИдЗначения))
                || empty($productOptions[(string) $property->Ид])
            ) {
                continue;
            }

            $attribute = $productOptions[(string) $property->Ид];
            $propertyValues = isset($property->ИдЗначения) ? $property->ИдЗначения : $property->Значение;

            foreach ($propertyValues as $propertyValue) {
                $propertyValue = trim((string) $propertyValue);

                /**
                 * Ignore node with empty value - <Значение/>.
                 *
                 * Example xml structure:
                 *
                 * <ЗначенияСвойства>
                 *     <Ид>5ff7fc04-d7d8-4c80-b6c6-46fe8bf9ceb2</Ид>
                 *     <Значение>28e4831d-d01b-11e2-aba7-001eec015c4c</Значение> (alt - <ИдЗначения>28e4831d-d01b-11e2-aba7-001eec015c4c</ИдЗначения>)
                 *     <Значение/>
                 * </ЗначенияСвойства>
                 */
                if ($propertyValue === '') {
                    continue;
                }

                /**
                 * Ignore attribute with full null value.
                 *
                 * Example xml structure:
                 *
                 * <ЗначенияСвойства>
                 *     <Ид>5ff7fc04-d7d8-4c80-b6c6-46fe8bf9ceb2</Ид>
                 *     <Значение>00000000-0000-0000-0000-000000000000</Значение>
                 * </ЗначенияСвойства>
                 */
                if ($propertyValue === '00000000-0000-0000-0000-000000000000') {
                    continue;
                }

                if (
                    $attribute['type'] === 'Справочник'
                    && isset($attribute['values'][$propertyValue])
                    && $attribute['values'][$propertyValue] !== ''
                ) {
                    $optionTermIDs = !empty($attribute['values'][$propertyValue])
                        ? [$attribute['values'][$propertyValue]]
                        : [];
                } else {
                    $optionTermIDs = [];
                    $propertyValue = self::applySeparator($propertyValue);

                    foreach ($propertyValue as $valuePart) {
                        // ignore empty
                        if ($valuePart === '') {
                            continue;
                        }

                        $uniqId1c = md5($attribute['createdTaxName'] . $valuePart);

                        $termID = Term::getTermIdByMeta($uniqId1c);

                        if (!$termID) {
                            $termID = get_term_by('name', \wp_slash($valuePart), $attribute['taxName']);

                            if ($termID) {
                                $termID = $termID->term_id;

                                Term::update1cId($termID, $uniqId1c);
                            }
                        }

                        if (!$termID) {
                            $term = ProductAttributeValueEntity::insert($valuePart, $attribute['taxName'], $uniqId1c);
                            $termID = $term['term_id'];

                            // default meta value by ordering
                            update_term_meta($termID, 'order_' . $attribute['taxName'], 0);

                            Term::update1cId($termID, $uniqId1c);
                        }

                        if ($termID) {
                            $optionTermIDs[] = $termID;
                        }
                    }
                }

                if (!empty($optionTermIDs)) {
                    if (!isset($setAttributes[$attribute['taxName']])) {
                        $setAttributes[$attribute['taxName']] = [];
                    }

                    foreach ($optionTermIDs as $termID) {
                        $setAttributes[$attribute['taxName']][] = (int) $termID;
                    }
                }
            }

            if (!empty($setAttributes[$attribute['taxName']])) {
                if (!isset($productAttributes[$attribute['taxName']])) {
                    $productAttributes[$attribute['taxName']] = [
                        'name' => \wc_clean($attribute['taxName']),
                        'value' => '',
                        'position' => 0,
                        'is_visible' => 1,
                        'is_variation' => 0,
                        'is_taxonomy' => 1,
                    ];
                }

                $productAttributes[$attribute['taxName']]['position'] = self::resolveAttributePosition(
                    $element,
                    (string) $property->Ид,
                    0
                );

                $currentAttributes[] = $attribute['taxName'];
            }
        }

        self::setAttributes($productId, $setAttributes, $productAttributes, $currentAttributes);
    }

    /**
     * Set resolved attributes to product.
     *
     * @param int   $productId
     * @param array $setAttributes
     * @param array $allAttributes
     * @param array $currentList
     *
     * @return void
     */
    private static function setAttributes($productId, $setAttributes, $allAttributes, $currentList)
    {
        $productOptions = get_option('all_product_options', []);

        if (empty($productOptions)) {
            return;
        }

        $productAttributeValues = [];

        if ($setAttributes) {
            foreach ($setAttributes as $tax => $values) {
                Term::setObjectTerms($productId, array_map('intval', $values), $tax);

                $productAttributeValues[$tax] = $values;
            }
        }

        // remove non exists attributes
        $resolved = $allAttributes;
        $allAttributeTaxes = \array_column($productOptions, 'taxName');

        foreach ($allAttributes as $key => $value) {
            if (empty($key)) {
                unset($resolved[$key]);

                continue;
            }

            // not check variation attribute
            if ($value['is_variation']) {
                continue;
            }

            // if not in current set and attribute was getting from 1C
            if (
                !in_array($key, $currentList, true)
                && in_array($key, $allAttributeTaxes, true)
            ) {
                unset($resolved[$key]);

                Term::setObjectTerms($productId, [], $key);
            }
        }

        Product::saveMetaValue($productId, '_product_attributes', $resolved);
        Product::saveMetaValue($productId, '_itglx_wc1c_attribute_values', $productAttributeValues);
    }

    /**
     * Sort attributes based on nomenclature category settings.
     *
     * @param \SimpleXMLElement $element
     * @param string            $attribute1cId
     * @param int               $position
     *
     * @return int
     */
    private static function resolveAttributePosition($element, $attribute1cId, $position)
    {
        if (empty($element->Категория)) {
            return $position;
        }

        $nomenclatureCategories = get_option('itglx_wc1c_nomenclature_categories', []);

        if (
            !$nomenclatureCategories
            || !isset($nomenclatureCategories[(string) $element->Категория])
            || empty($nomenclatureCategories[(string) $element->Категория]['options'])
        ) {
            return $position;
        }

        return (int) array_search(
            $attribute1cId,
            $nomenclatureCategories[(string) $element->Категория]['options'],
            true
        );
    }

    /**
     * The method applies the separator specified in the settings.
     *
     * If the delimiter is not specified or applying is not enabled, then the same value will be returned,
     * but as the first element of the array.
     *
     * @param string $value
     *
     * @return array
     */
    private static function applySeparator($value)
    {
        // if no enabled
        if (SettingsHelper::isEmpty('property_use_separator_in_value')) {
            return [$value];
        }

        $separator = trim(SettingsHelper::get('property_separator_value', ''));

        // if separator not specified
        if ($separator === '') {
            return [$value];
        }

        $value = explode($separator, $value);

        return array_map('trim', $value);
    }
}
