<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeEntity;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FindAttribute
{
    public function __construct()
    {
        /**
         * @since 1.89.0
         */
        if (!SettingsHelper::isEmpty('merge_properties_with_same_name')) {
            \add_filter('itglx_wc1c_find_exists_product_attribute', [$this, 'merge'], 10, 2);
        }
    }

    /**
     * @param null|object       $attribute
     * @param \SimpleXMLElement $element
     *
     * @return null|object
     */
    public function merge($attribute, $element)
    {
        if ($attribute) {
            return $attribute;
        }

        if (empty($element->Ид) || empty($element->Наименование)) {
            return $attribute;
        }

        $attribute = ProductAttributeEntity::getByLabel((string) $element->Наименование);

        // if not found, then we will immediately return the result
        if (!$attribute) {
            return $attribute;
        }

        Logger::log(
            '(attribute) merged - '
            . $attribute->attribute_id
            . ($attribute->id_1c ? ', GUID - ' . $attribute->id_1c : ''),
            [(string) $element->Наименование, (string) $element->Ид]
        );

        $options = \get_option('all_product_options', []);

        // set the required values in the data cache from the existing one
        if (
            !isset($options[(string) $element->Ид])
            && $attribute->id_1c
            && isset($options[$attribute->id_1c])
        ) {
            // it is necessary to have the same value `createdTaxName` for the hash of values to match
            $options[(string) $element->Ид] = [
                'taxName' => $options[$attribute->id_1c]['taxName'],
                'createdTaxName' => $options[$attribute->id_1c]['createdTaxName'],
                'type' => '',
                'values' => [],
            ];

            update_option('all_product_options', $options);
        }

        return $attribute;
    }
}
