<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureForImagesTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('For Images', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomenclature-images',
            'fields' => [
                'write_product_name_to_attachment_title' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Write the name of the product in the title of the media file',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, then in the title of the added media files (images) will be written the name of '
                        . 'the product to which the picture belongs. Please note that the title will be write '
                        . 'only for new or changed pictures.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'write_product_name_to_attachment_attribute_alt' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Write the name of the product in the "Attribute Alt" of the media file',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, the name of the product to which the picture belongs will be write in '
                        . 'the metadata `_wp_attachment_image_alt` added media files (images). Please note '
                        . 'that the metadata will be write only for new or changed pictures.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'more_check_image_changed' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Extra control over image changes', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'Turn this option on if you notice that changing the image in 1C does not lead '
                        . 'to a change on the site. This can occur in a number of configurations in '
                        . 'which the file name does not change when the image is changed.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'images_not_delete_related_when_delete_product' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Do not delete product related images when deleting a product', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'By default, when deleting a product the media file that is set by the main image '
                        . 'of the product is deleted, as well as the media files that are specified in its gallery.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'images_not_delete_related_when_delete_variation' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Do not delete variation image when deleting it', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'By default, when deleting a variation, the media file that is specified by its image is also deleted.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
