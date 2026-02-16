<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrdersTabs;

use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrders;

class SectionExchangeOrdersAdditionalTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('Unloading orders (additional)', 'itgalaxy-woocommerce-1c'),
            'id' => 'unload-orders-additional',
            'fields' => [
                'send_orders_use_product_id_from_site' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Use product id from the site (if there is no 1С guid)', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then when generating data on products, if the product / variation is not '
                        . 'connected with the data from the uploading from 1C (does not have a guid), then the '
                        . 'product / variation id will be added to the "Ид" node, otherwise if the product / '
                        . 'variation is not associated with data upload from 1C, node "Ид" will not be added.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('For positions without GUID', 'itgalaxy-woocommerce-1c'),
                ],
                'send_orders_use_variation_characteristics_from_site' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Generate attribute data for variations (if there is no 1С guid)',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, then when generating data about goods, if this is a variation and it does'
                        . 'not have a guid, that is, it is not associated with unloading data, then generate'
                        . 'data on the attributes and values of the variation in node "ХарактеристикиТовара".',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'send_orders_combine_data_variation_as_main_product' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Combine data on variations and pass it as one line with the main product',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_set_currency_by_order_data' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Specify currency according to order data', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__('By default the currency is used from the base price type.', 'itgalaxy-woocommerce-1c'),
                ],
                'send_orders_tax_data_from_order' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Add information about the tax, according to the order', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then the order document and each item will have information about the tax '
                        . '(rate and amount) according to the order data. Tax information is uploaded with attribute '
                        . '`УчтеноВСумме` in the value `true`.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_add_information_discount_for_each_item' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Add information about a discount for each item of the order, if there is a discount',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, then if there is a discount for the order item, the information about the '
                        . 'discount amount will be added to the unloading of the item. Discount information is '
                        . 'uploaded with attribute `УчтеноВСумме` in the value `true`.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_do_not_generate_contragent_data' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Do not generate data on the contragent', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then data on the contragent, that is "Контрагенты->Контрагент", is not'
                        . 'added to the order.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Contragent', 'itgalaxy-woocommerce-1c'),
                ],
                'send_orders_division_contragent_into_ind_and_legal' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Division of contragents into individuals and legal', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then when company field is filled in the order, the contragent will be'
                        . 'unloaded as a legal, otherwise, as individual. By default, the contragent is '
                        . 'always unloaded as individual.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'send_orders_status_is_paid' => [
                    'title' => esc_html__('Order statuses for prop `Заказ оплачен` = `true`:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'select2',
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                    'description' => esc_html__(
                        'Select the order statuses at which you want to transfer the requisite in the value '
                        . '`true`, if the order status is not one of the selected, `false` will be transferred.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Requisite `Заказ оплачен`', 'itgalaxy-woocommerce-1c'),
                ],
                'send_orders_payment_method_is_paid' => [
                    'title' => esc_html__('Payment methods for prop `Заказ оплачен` = `true`:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'select2',
                    'options' => SectionExchangeOrders::getPaymentGatewayList(),
                    'description' => esc_html__(
                        'Select the payment methods at which you want to transfer the requisite in the value '
                        . '`true`, if the order status is not one of the selected, `false` will be transferred.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
                'send_orders_status_mapping' => [
                    'title' => esc_html__('Names of order statuses for 1C', 'itgalaxy-woocommerce-1c'),
                    'type' => 'send_orders_status_mapping',
                    'description' => esc_html__(
                        'Use this setting if you want to set a lower bound for the date of creation of the '
                        . 'order that can be unloaded. If the order is created earlier than this date, '
                        . 'it will never be unloaded.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
