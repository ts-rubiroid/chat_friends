<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureSkipExcludeTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('Skipping / excluding data', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomenclature-skipping-data',
            'fields' => self::getFields(),
        ];
    }

    private static function getFields()
    {
        return [
            'skip_categories' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not process groups', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, then categories on the site will not be created / updated based on '
                    . 'data about groups from 1C, and the category will not be assigned / changed to '
                    . 'products.',
                    'itgalaxy-woocommerce-1c'
                ),
                'fieldsetStart' => true,
                'legend' => esc_html__('Categories (groups)', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_product_cat_name' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not update category name', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, the category name will be writed when the category is created and '
                    . 'will no longer be changed according to the upload data.',
                    'itgalaxy-woocommerce-1c'
                ),
                'fieldsetEnd' => true,
            ],
            'skip_update_attribute_label' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not update attribute label', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, the attribute label will be writed when the attribute is created and '
                    . 'will no longer be changed according to the upload data.',
                    'itgalaxy-woocommerce-1c'
                ),
                'fieldsetStart' => true,
                'legend' => esc_html__('Attributes (options)', 'itgalaxy-woocommerce-1c'),
                'fieldsetEnd' => true,
            ],
            'skip_products' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not process products', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, the products on the site will not be created / updated based on product data from the 1C upload.',
                    'itgalaxy-woocommerce-1c'
                ),
                'fieldsetStart' => true,
                'legend' => esc_html__('Products (main data)', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_products_without_photo' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip products without photo', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, then products without photos will not be added to the site.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_post_content_excerpt' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product description', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, description and except will not be writed or modified.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_post_title' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not update product title', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, the product title will be writed when the product is created and '
                    . 'will no longer be changed according to the upload data.',
                    'itgalaxy-woocommerce-1c'
                ),
            ],
            'skip_product_weight' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product weight', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, weight will not be writed or modified.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_product_sizes' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product sizes (length, width and height)', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, sizes will not be writed or modified.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_post_images' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not update product images', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, the product images will be writed when the product is created '
                    . '(if there is) and will no longer be changed according to the upload data.',
                    'itgalaxy-woocommerce-1c'
                ),
            ],
            'skip_post_attributes' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not update product attributes', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, the product attributes will be writed when the product is created and '
                    . 'will no longer be changed according to the upload data.',
                    'itgalaxy-woocommerce-1c'
                ),
            ],
            'skip_product_manufacturer' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product manufacturer', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, then data from "Товар->Изготовитель" will not be processed.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_product_country' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product country', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, then data from "Товар->Страна" will not be processed.', 'itgalaxy-woocommerce-1c'),
                'fieldsetEnd' => true,
            ],
            'skip_offers' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not process offers', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, offer data will not be processed.', 'itgalaxy-woocommerce-1c'),
                'fieldsetStart' => true,
                'legend' => esc_html__('Offers (price, stock, offer characteristics)', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_product_prices' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product prices', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, prices will not be writed or modified.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_product_stocks' => [
                'type' => 'checkbox',
                'title' => esc_html__('Skip product stocks', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__('If enabled, stocks will not be writed or modified.', 'itgalaxy-woocommerce-1c'),
            ],
            'skip_update_set_attribute_for_variations' => [
                'type' => 'checkbox',
                'title' => esc_html__('Do not change product attribute set for variations', 'itgalaxy-woocommerce-1c'),
                'description' => esc_html__(
                    'If enabled, then the set of attributes that are used for the variations will be writed when '
                    . 'the product is created and will no longer be changed according to the upload data.',
                    'itgalaxy-woocommerce-1c'
                ),
                'fieldsetEnd' => true,
            ],
        ];
    }
}
