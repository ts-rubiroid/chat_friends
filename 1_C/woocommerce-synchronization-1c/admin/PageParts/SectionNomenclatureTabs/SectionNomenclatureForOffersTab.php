<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureTabs;

class SectionNomenclatureForOffersTab
{
    /**
     * @return array
     */
    public static function getSettings()
    {
        return [
            'title' => esc_html__('For offers', 'itgalaxy-woocommerce-1c'),
            'id' => 'nomeclature-offers',
            'fields' => [
                'products_stock_null_rule' => [
                    'type' => 'select',
                    'title' => esc_html__('With a stock <= 0:', 'itgalaxy-woocommerce-1c'),
                    'options' => [
                        '0' => esc_html__(
                            'Hide (not available for viewing and ordering)',
                            'itgalaxy-woocommerce-1c'
                        ),
                        '1' => esc_html__(
                            'Do not hide and give the opportunity to put in the basket',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'not_hide_and_put_basket_with_disable_manage_stock_and_stock_status_onbackorder' => esc_html__(
                            'Do not hide and give the opportunity to put in the basket (Manage stock - '
                            . 'disable, Stock status - On back order)',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_notify' => esc_html__(
                            'Do not hide and give the opportunity to put in the basket (Manage stock - '
                            . 'default, Allow backorders? - notify)',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'not_hide_and_put_basket_with_default_manage_stock_and_allow_backorders_yes' => esc_html__(
                            'Do not hide and give the opportunity to put in the basket (Manage stock - '
                            . 'default, Allow backorders? - yes)',
                            'itgalaxy-woocommerce-1c'
                        ),
                        '2' => esc_html__(
                            'Do not hide, but do not give the opportunity to put in the basket',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'with_negative_not_hide_and_put_basket_with_zero_hide_and_not_put_basket' => esc_html__(
                            'Do not hide with a negative stock and give an opportunity to put in a basket, '
                            . 'with a zero stock hide and do not give an opportunity to put in a basket.',
                            'itgalaxy-woocommerce-1c'
                        ),
                    ],
                    'description' => esc_html__(
                        'Only products/variations with a non-empty price can be opened.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Stock actions', 'itgalaxy-woocommerce-1c'),
                ],
                'products_onbackorder_stock_positive_rule' => [
                    'type' => 'select',
                    'title' => esc_html__('With a stock > 0 (Allow backorders?):', 'itgalaxy-woocommerce-1c'),
                    'options' => [
                        'no' => esc_html__('Do not allow', 'itgalaxy-woocommerce-1c'),
                        'notify' => esc_html__('Allow, but notify customer', 'itgalaxy-woocommerce-1c'),
                        'yes' => esc_html__('Allow', 'itgalaxy-woocommerce-1c'),
                    ],
                ],
                'offers_do_not_check_has_price_in_stock_behavior' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Don\'t care if there\'s a price or not', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'By default, if there is no price, then regardless of the stock, hiding is applied and the stock status is "out of stock".',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
            ]
            + self::getSeparateByWarehousesSettings()
            + [
                'offers_delete_variation_if_offer_marked_deletion' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Remove variation if the variable (complex) offer is marked for removal', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, when an variable (complex) offer related to variation is received marked for deletion '
                        . '(that is, a characteristic was marked for deletion), the variation will be deleted. '
                        . 'By default, variation is only disabled.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'offers_fired_save_simple_product_when_change_price_stock' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Call method `save` for simple products when price/stock changes',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'This is not usually required, but may be necessary, for example, for compatibility with '
                        . 'various feed generation plugins, etc.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private static function getSeparateByWarehousesSettings()
    {
        $warehouses = get_option('all_1c_stocks', []);

        if (empty($warehouses)) {
            return [
                'offers_warehouses_account_stock_rule' => [
                    'type' => 'content',
                    'content' => '<p class="description">'
                        . esc_html__('The settings will be available if data on warehouses is received in the unloading.', 'itgalaxy-woocommerce-1c')
                        . '</p>',
                    'legend' => esc_html__('Stock with division by warehouses', 'itgalaxy-woocommerce-1c'),
                    'fieldsetStart' => true,
                    'fieldsetEnd' => true,
                ],
            ];
        }

        $list = [];

        foreach ($warehouses as $guid => $warehouse) {
            $list[$guid] = $warehouse['Наименование'] . ' (' . $guid . ')';
        }

        return [
            'offers_warehouses_account_stock_rule' => [
                'type' => 'select',
                'title' => esc_html__('Account for the stock:', 'itgalaxy-woocommerce-1c'),
                'options' => [
                    '' => esc_html__('All warehouses', 'itgalaxy-woocommerce-1c'),
                    'selected' => esc_html__('Selected warehouses', 'itgalaxy-woocommerce-1c'),
                    'not_selected' => esc_html__('Not selected warehouses', 'itgalaxy-woocommerce-1c'),
                ],
                'description' => esc_html__(
                    'If you have chosen the option to take into account stocks not in all warehouses, then do not '
                    . 'forget to select warehouses in the field below.',
                    'itgalaxy-woocommerce-1c'
                ),
                'fieldsetStart' => true,
                'legend' => esc_html__('Stock with division by warehouses', 'itgalaxy-woocommerce-1c'),
            ],
            'offers_warehouses_selected_to_account_stock' => [
                'type' => 'select2',
                'title' => esc_html__('Warehouses:', 'itgalaxy-woocommerce-1c'),
                'options' => $list,
                'fieldsetEnd' => true,
                'description' => esc_html__(
                    'The list of warehouses is formed on the basis of the data received in the unloading.',
                    'itgalaxy-woocommerce-1c'
                ),
            ],
        ];
    }
}
