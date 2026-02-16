<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureForAttributesTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('For Attributes', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomenclature-attributes',
            'fields' => [
                'merge_properties_with_same_name' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('An attempt to search for basic (generated from properties) attributes by name and merge properties with the same name', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, properties (both basic and for offers) with the same name will be merged'
                        . 'into one final attribute. This will be useful when nomenclature categories are '
                        . 'applied and, as a result, several properties are obtained in the unloading and on'
                        . 'the basis of them separate attributes are created, which is true in structure, '
                        . 'but in reality it interferes and looks like duplicates.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'find_exists_attribute_value_by_name' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'An attempt to search for basic (generated from properties) attribute values by name',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, then the plugin tries to find the attribute value by name, if it is not '
                        . 'found by ID from 1C. It may be useful if the site already has attribute values and, in '
                        . 'order not to create everything again, you can make their first link by name.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'property_use_separator_in_value' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Apply separator to values', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, when processing product property values, the value can be split into '
                        . 'several using the separator specified in the field below. Can be applied only to '
                        . 'simple (not "Справочник") basic properties of the product. It can be useful, since '
                        . 'most configurations do not allow you to specify multiple values for 1 property in 1 '
                        . 'product, as a result, you can specify several values, like one with a separator, '
                        . 'and as a result, the product will not have 1 value, but several on the site.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Separator in values', 'itgalaxy-woocommerce-1c'),
                ],
                'property_separator_value' => [
                    'type' => 'text',
                    'title' => esc_html__('Separator value', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'For example, you specified "value 1||value 2||value 3" in the property, then to get '
                        . '3 values, specify "||" to separate.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'attribute_create_enable_public' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Enable archives when creating attributes', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, the checkbox "Enable archives?" will be enabled for the created attributes.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'attribute_variable_enable_visibility' => [
                    'type' => 'checkbox',
                    'title' => \esc_html__('Enable visibility for variable product attributes', 'itgalaxy-woocommerce-1c'),
                    'description' => \esc_html__(
                        'If enabled, the "Visible on the product page" checkbox will be enabled for attributes '
                        . 'that are used for variations in the product. By default, only "Used for variations" '
                        . 'is checked for such attributes.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
