<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeValueEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Parsing and saving data on the attributes of a specific variation.
 */
class VariationOfferAttributes
{
    /**
     * Parsing characteristics and values for variation according to data in XML.
     *
     * @param \SimpleXMLElement $element
     * @param array             $variationEntry
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function processCharacteristics($element, $variationEntry)
    {
        $currentAttributeValues = [];
        $productOptions = get_option('all_product_options', []);

        foreach ($element->ХарактеристикиТовара->ХарактеристикаТовара as $property) {
            if (empty($property->Значение) || empty($property->Наименование)) {
                continue;
            }

            $label = (string) $property->Наименование;
            $value = (string) $property->Значение;

            $taxByLabel = trim(strtolower($label));
            $taxByLabel = hash('crc32', $taxByLabel);

            $attributeName = 'simple_' . $taxByLabel;

            if (empty($productOptions[$attributeName])) {
                continue;
            }

            $attribute = $productOptions[$attributeName];
            $uniqId1c = md5($attribute['createdTaxName'] . $value);
            $optionTermID = Term::getTermIdByMeta($uniqId1c);

            /**
             * If you didn’t find it by a unique key, try to find it by name.
             */
            if (!$optionTermID) {
                $optionTermID = get_term_by('name', \wp_slash($value), $attribute['taxName']);

                if ($optionTermID) {
                    $optionTermID = $optionTermID->term_id;

                    Term::update1cId($optionTermID, $uniqId1c);
                }
            }

            if (!$optionTermID) {
                $term = ProductAttributeValueEntity::insert($value, $attribute['taxName'], $uniqId1c);

                $optionTermID = $term['term_id'];

                // default meta value by ordering
                update_term_meta($optionTermID, 'order_' . $attribute['taxName'], 0);

                Term::update1cId($optionTermID, $uniqId1c);
            }

            if (!$optionTermID) {
                continue;
            }

            $currentAttributeValues[$attribute['taxName']] = $optionTermID;

            ProductVariation::saveMetaValue(
                $variationEntry['ID'],
                'attribute_' . \esc_attr(\sanitize_title($attribute['taxName'])),
                get_term_by('id', $optionTermID, $attribute['taxName'])->slug,
                $variationEntry['post_parent']
            );

            $_SESSION['IMPORT_1C']['setTerms'][$variationEntry['post_parent']][$attribute['taxName']][] = $optionTermID;
        }

        ProductVariation::saveMetaValue(
            $variationEntry['ID'],
            '_itglx_wc1c_attributes_state',
            $currentAttributeValues,
            $variationEntry['post_parent']
        );
    }

    /**
     * Parsing options and values for variation according to data in XML.
     *
     * @param \SimpleXMLElement $element
     * @param array             $variationEntry
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function processOptions($element, $variationEntry)
    {
        $currentAttributeValues = [];
        $productOptions = get_option('all_product_options', []);

        /**
         * Filters the list of product properties to be ignored during processing.
         *
         * @since 1.74.1
         *
         * @param string[] $ignoreAttributeProcessing Array of strings with property guid to be ignored during processing.
         */
        $ignoreAttributeProcessing = \apply_filters('itglx_wc1c_attribute_ignore_guid_array', []);

        foreach ($element->ЗначенияСвойств->ЗначенияСвойства as $property) {
            $propertyGuid = (string) $property->Ид;

            /**
             * @see https://developer.wordpress.org/reference/functions/has_action/
             */
            if (has_action('itglx_wc1c_offer_option_custom_processing_' . $propertyGuid)) {
                /**
                 * The action allows to organize custom processing of the values of which property of the offer.
                 *
                 * @since 1.88.2
                 *
                 * @param array             $variationEntry
                 * @param \SimpleXMLElement $property
                 */
                \do_action('itglx_wc1c_offer_option_custom_processing_' . $propertyGuid, $variationEntry, $property);

                continue;
            }

            $propertyValue = isset($property->ИдЗначения) ? (string) $property->ИдЗначения : (string) $property->Значение;

            if ($propertyValue === '' || empty($productOptions[$propertyGuid])) {
                continue;
            }

            if (in_array($propertyGuid, $ignoreAttributeProcessing, true)) {
                continue;
            }

            $attribute = $productOptions[$propertyGuid];

            if ($attribute['type'] === 'Справочник') {
                $optionTermID = isset($attribute['values'][$propertyValue])
                    ? $attribute['values'][$propertyValue]
                    : false;

                /**
                 * If the value in the current data does not have a term corresponding to the value, then we will try to find it.
                 *
                 * It can be useful, since there are cases when the 1С module does not form part of the values
                 * in the main data on properties. May occur when using batch unloading.
                 *
                 * If the value is actually on the site, then this will be the solution to the problem.
                 * Otherwise, the product will not receive this value in the attribute and the variation will have
                 * an empty value for this attribute.
                 */
                if ($optionTermID === false) {
                    $optionTermID = Term::getTermIdByMeta(md5($propertyValue . $attribute['createdTaxName']));

                    if ($optionTermID) {
                        Logger::log(
                            "(variation) found through fallback, value - {$propertyValue} , property - {$propertyGuid}",
                            [(string) $element->Ид]
                        );
                    }
                }

                // If the specified value does not exist
                if (empty($optionTermID)) {
                    Logger::log(
                        "(variation) no value for property, value - {$propertyValue} , property - {$propertyGuid}",
                        [(string) $element->Ид],
                        'warning'
                    );
                }
            } else {
                $uniqId1c = md5($attribute['createdTaxName'] . $propertyValue);
                $optionTermID = Term::getTermIdByMeta($uniqId1c);

                /**
                 * If you didn’t find it by a unique key, try to find it by name.
                 */
                if (!$optionTermID) {
                    $optionTermID = get_term_by('name', \wp_slash($propertyValue), $attribute['taxName']);

                    if ($optionTermID) {
                        $optionTermID = $optionTermID->term_id;

                        Term::update1cId($optionTermID, $uniqId1c);
                    }
                }

                if (!$optionTermID) {
                    $term = ProductAttributeValueEntity::insert($propertyValue, $attribute['taxName'], $uniqId1c);

                    $optionTermID = $term['term_id'];

                    // default meta value by ordering
                    update_term_meta($optionTermID, 'order_' . $attribute['taxName'], 0);

                    Term::update1cId($optionTermID, $uniqId1c);
                }
            }

            if (!$optionTermID) {
                continue;
            }

            $currentAttributeValues[$attribute['taxName']] = $optionTermID;

            ProductVariation::saveMetaValue(
                $variationEntry['ID'],
                'attribute_' . \esc_attr(\sanitize_title($attribute['taxName'])),
                get_term_by('id', $optionTermID, $attribute['taxName'])->slug,
                $variationEntry['post_parent']
            );

            $_SESSION['IMPORT_1C']['setTerms'][$variationEntry['post_parent']][$attribute['taxName']][] = $optionTermID;
        }

        ProductVariation::saveMetaValue(
            $variationEntry['ID'],
            '_itglx_wc1c_attributes_state',
            $currentAttributeValues,
            $variationEntry['post_parent']
        );
    }

    /**
     * Allows to check for the presence of characteristics nodes.
     *
     * @param \SimpleXMLElement $element
     *
     * @return bool
     */
    public static function hasCharacteristics($element)
    {
        return isset($element->ХарактеристикиТовара) && isset($element->ХарактеристикиТовара->ХарактеристикаТовара);
    }

    /**
     * Allows to check for the presence of options nodes.
     *
     * @param \SimpleXMLElement $element
     *
     * @return bool
     */
    public static function hasOptions($element)
    {
        return isset($element->ЗначенияСвойств) && isset($element->ЗначенияСвойств->ЗначенияСвойства);
    }
}
