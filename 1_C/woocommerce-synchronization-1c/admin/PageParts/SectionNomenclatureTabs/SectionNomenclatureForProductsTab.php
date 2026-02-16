<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureForProductsTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('For products', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomeclature-products',
            'fields' => [
                'find_product_by_sku' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Try to find a product by SKU', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then the plugin tries to find the product by SKU, if it is not '
                        . 'found by ID from 1C. It may be useful if the site already has products and, in '
                        . 'order not to create everything again, you can make their first link by SKU.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'product_create_new_in_status_draft' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Create a new product in the status "Draft"', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, a new product will be added in the status "Draft", by default the '
                        . 'product is created in the status "Publish".',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'product_use_full_name' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Use full name', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, the title of the product will be recorded not "Name" and "Full Name" '
                        . 'of the details of the products.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Product title', 'itgalaxy-woocommerce-1c'),
                    'fieldsetEnd' => true,
                ],
                'write_product_description_in_excerpt' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Write the "Description" in a short description of the product.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, the product description will be written in a short description '
                        . '(post_excerpt).',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Product description', 'itgalaxy-woocommerce-1c'),
                ],
                'use_html_description' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Use for the main description "Description file for the site"',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If it is included, then the description of the product will be recorded not in '
                        . 'the "Description", but in the "Description in HTML format" from the details of '
                        . 'the product, if any, while the data from the "Description" will be recorded in '
                        . 'a excerpt description of the product.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'use_separate_file_with_html_description' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Use a separate file with a description', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, the plugin will fill in product description from the first "*.html" file '
                        . 'in the upload, if the file exists. If there is a file, then data from "Описание" '
                        . 'will be written into a excerpt description.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'get_product_sku_from' => [
                    'type' => 'select',
                    'title' => esc_html__('Get product sku from:', 'itgalaxy-woocommerce-1c'),
                    'options' => [
                        'sku' => esc_html__('SKU (data from node "Товар->Артикул")', 'itgalaxy-woocommerce-1c'),
                        'requisite_code' => esc_html__('Requisite value "Code"', 'itgalaxy-woocommerce-1c'),
                        'code' => esc_html__('Code (data from node "Товар->Код")', 'itgalaxy-woocommerce-1c'),
                        'requisite_barcode' => esc_html__('Requisite value "Barcode"', 'itgalaxy-woocommerce-1c'),
                        'barcode' => esc_html__('Barcode (data from node "Товар->Штрихкод")', 'itgalaxy-woocommerce-1c'),
                    ],
                    'description' => esc_html__(
                        'Indicate from which value the article number should be written.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'product_weight_use_factor' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Apply factor to original value', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'It can be useful if the unit of weight on the site and in 1C is different. '
                        . 'By applying a factor to the original value, you can convert the weight value from '
                        . 'the unloading to the unit that is used on the site.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Product weight', 'itgalaxy-woocommerce-1c'),
                ],
                'product_weight_factor_value' => [
                    'type' => 'number',
                    'step' => '0.001',
                    'title' => esc_html__('Factor value', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'For example, on the site, grams are used, and in 1C, the weight is indicated in '
                        . 'kilograms. To get the value in grams, you need to use a factor of 1000. '
                        . 'Formula: initial value * factor = value.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'skip_change_product_visibility' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Do not affect the visibility of the product in the catalog / search', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, the visibility of the product in the catalog / search will not change based on the unloading data.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
