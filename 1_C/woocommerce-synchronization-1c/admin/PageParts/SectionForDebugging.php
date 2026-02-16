<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

class SectionForDebugging
{
    public static function render()
    {
        $section = [
            'title' => esc_html__('For debugging', 'itgalaxy-woocommerce-1c'),
            'fields' => [
                'not_delete_exchange_files' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Do not delete files received from 1C', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'It may be useful during debugging to analyze the contents of the received XML files. Please '
                        . 'do not leave the option turned on for several exchanges unless you have enabled '
                        . '"Exchange in archive", otherwise the exchange files will not be overwritten, and the data '
                        . 'will be added to existing ones, which creates invalid xml.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'ignore_catalog_file_processing' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Ignore processing of received files', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'It can be useful if, for some reason, you only want to receive and save files transferred '
                        . 'from 1C, but not process them.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'force_update_product' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Force update products', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'Ignore the control of product changes by hash of the contents and update anyway. '
                        . 'It may be useful, for example, if you made changes in the administrative panel, '
                        . 'and not in 1C, and now you want to overwrite the data.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'remove_missing_variation' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Clean up missing product variations', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then all variations of goods that are not in the unloading will be '
                        . 'deleted. Be careful and use it only with a full exchange!',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];

        Section::render($section);
    }
}
