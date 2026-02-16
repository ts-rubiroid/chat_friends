<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer\VariationOfferAttributes;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeEntity;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class VariationCharacteristicsToGlobalProductAttributes
{
    /**
     * @param \SimpleXMLElement $element
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function process($element)
    {
        if (!VariationOfferAttributes::hasCharacteristics($element)) {
            return;
        }

        $options = get_option('all_product_options', []);

        /**
         * Example xml structure.
         *
         * <ХарактеристикиТовара>
         *     <ХарактеристикаТовара>
         *         <Наименование>Размер</Наименование>
         *         <Значение>L</Значение>
         *     </ХарактеристикаТовара>
         *     <ХарактеристикаТовара>
         *         <Наименование>Цвет</Наименование>
         *         <Значение>Красный</Значение>
         *     </ХарактеристикаТовара>
         * </ХарактеристикиТовара>
         */
        foreach ($element->ХарактеристикиТовара->ХарактеристикаТовара as $property) {
            $label = (string) $property->Наименование;
            $taxByLabel = trim(strtolower($label));
            $taxByLabel = hash('crc32', $taxByLabel);

            $attributeName = 'simple_' . $taxByLabel;
            $attributeTaxName = 'pa_' . $attributeName;

            $attribute = ProductAttributeEntity::get($attributeTaxName);

            // exists
            if ($attribute && isset($options[$attributeName])) {
                $options[$attributeName]['taxName'] = 'pa_' . $attribute->attribute_name;

                if (!isset($options[$attributeName]['createdTaxName'])) {
                    $options[$attributeName]['createdTaxName'] = $options[$attributeName]['taxName'];
                }

                update_option('all_product_options', $options);

                continue;
            }

            $attributeCreate = ProductAttributeEntity::insert($label, $attributeName, $attributeTaxName);

            Logger::log('(attribute) created attribute by data `ХарактеристикиТовара`', $attributeCreate);

            $attributeTaxName = 'pa_' . $attributeCreate['attribute_name'];

            $options[$attributeName] = [
                'taxName' => $attributeTaxName,
                'createdTaxName' => $attributeTaxName,
                'type' => 'simple',
                'values' => [],
            ];

            /**
             * To use the taxonomy right after the attribute is created, we need to register it. The next time
             * the site is loaded, the taxonomy will already be registered with the WooCommerce logic,
             * so we only need it now.
             *
             * @see https://developer.wordpress.org/reference/functions/register_taxonomy/
             */
            \register_taxonomy($attributeTaxName, null);
        }

        if (count($options)) {
            update_option('all_product_options', $options);
        }
    }
}
