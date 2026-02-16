<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureForCategoriesTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('For Categories', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomenclature-categories',
            'fields' => [
                'find_product_cat_term_by_name' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Try to find a category by name', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then the plugin tries to find the category by name, if it is not '
                        . 'found by ID from 1C. It may be useful if the site already has categories and, in '
                        . 'order not to create everything again, you can make their first link by name.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'set_category_thumbnail_by_product' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Set category thumbnails automatically', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, the category will be assigned a picture for the first product with an '
                        . 'image when processing the upload, if the product has a direct link to the category, '
                        . 'that is, it is linked directly.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
