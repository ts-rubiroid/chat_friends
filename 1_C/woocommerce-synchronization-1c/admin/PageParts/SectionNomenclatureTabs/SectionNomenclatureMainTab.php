<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureMainTab
{
    public static function getSettings()
    {
        $userList = [];

        foreach (get_users(['role' => 'administrator']) as $user) {
            $userList[$user->ID] = $user->user_login;
        }

        return [
            'title' => esc_html__('Main', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomenclature-main',
            'fields' => [
                'exchange_post_author' => [
                    'type' => 'select',
                    'title' => esc_html__('Product / Image Owner', 'itgalaxy-woocommerce-1c'),
                    'options' => $userList,
                ],
                'file_limit' => [
                    'type' => 'number',
                    'title' => esc_html__('File part size:', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'The maximum size of the part of the exchange files transmitted from 1C (in bytes).',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'default' => 5000000,
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Exchange parameters', 'itgalaxy-woocommerce-1c'),
                ],
                'time_limit' => [
                    'type' => 'number',
                    'title' => esc_html__('Script running time (second):', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'Maximum time the sync script runs (in seconds), for one step processing progress. '
                        . 'Recommended value: 20, this is suitable for most hosts.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'default' => 20,
                ],
                'use_file_zip' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Exchange in the archive', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, the exchange takes place through a zip archive.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'remove_missing_full_unload_products' => [
                    'type' => 'select',
                    'title' => esc_html__('Remove missing products', 'itgalaxy-woocommerce-1c'),
                    'options' => [
                        '' => esc_html__('Not chosen', 'itgalaxy-woocommerce-1c'),
                        'completely' => esc_html__('complete removal', 'itgalaxy-woocommerce-1c'),
                        'trash' => esc_html__('to trash', 'itgalaxy-woocommerce-1c'),
                    ],
                    'description' => esc_html__(
                        'If chosen, all products that are missing in the unloading will be deleted '
                        . ' (if the product is created manually and is not related to the data from the upload, '
                        . 'then they will not be affected).',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Full unload (not only changes)', 'itgalaxy-woocommerce-1c'),
                ],
                'remove_missing_full_unload_product_categories' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Remove missing categories', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, all categories that are missing in the unloading will be deleted '
                        . ' (if the category is created manually and is not related to the data from the upload, '
                        . 'then they will not be affected).',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'remove_marked_products_to_trash' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Remove products marked for deletion (according to the uploading data) to trash', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If the product has been uploaded and, according to the unloading data, it is marked for'
                        . ' deletion, then the product will be removed to trash. By default, the product is '
                        . 'removed completely.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'restore_products_from_trash' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Restore product from trash (status `trash`)', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If the product is in unloading, does not have a deletion mark and the related product '
                        . 'on the site has been removed to the trash (has status `trash`), then the product '
                        . 'will be restored from the trash. By default, the product in the trash '
                        . 'will simply be updated.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
