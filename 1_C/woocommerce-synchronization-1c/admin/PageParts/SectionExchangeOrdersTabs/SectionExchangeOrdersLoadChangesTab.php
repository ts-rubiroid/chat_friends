<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrdersTabs;

use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrders;

class SectionExchangeOrdersLoadChangesTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('Loading changes (for previously unloaded orders)', 'itgalaxy-woocommerce-1c'),
            'id' => 'upload-orders',
            'fields' => [
                'handle_get_order_status_change' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Handle status change', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, when exchanging with 1C, then the site will accept and process changes in '
                        . 'the status of the order when 1C sends this data.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Change of status by requisites', 'itgalaxy-woocommerce-1c'),
                ],
                'handle_get_order_status_change_if_paid' => [
                    'type' => 'select',
                    'title' => esc_html__('Order status, if there is "Дата оплаты по 1С":', 'itgalaxy-woocommerce-1c'),
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                ],
                'handle_get_order_status_change_if_shipped' => [
                    'type' => 'select',
                    'title' => esc_html__('Order status, if there is "Дата отгрузки по 1С":', 'itgalaxy-woocommerce-1c'),
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                ],
                'handle_get_order_status_change_if_paid_and_shipped' => [
                    'type' => 'select',
                    'title' => esc_html__(
                        'Order status, if there is "Дата оплаты по 1С" and "Дата отгрузки по 1С":',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                ],
                'handle_get_order_status_change_if_passed' => [
                    'type' => 'select',
                    'title' => esc_html__('Order status if "Проведен" = "true":', 'itgalaxy-woocommerce-1c'),
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                ],
                'handle_get_order_status_change_if_cancelled' => [
                    'type' => 'select',
                    'title' => esc_html__('Order status if "Отменен" = "true":', 'itgalaxy-woocommerce-1c'),
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                ],
                'handle_get_order_status_change_if_document_amount_zero' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Consider document with sum 0 (tag "Сумма") as having requisite "Отменен" = "true"', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'It can be useful if the module in 1C does not unload the cancellation details in the data, but instead the document has a zero amount.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'handle_get_order_status_change_if_deleted' => [
                    'type' => 'select',
                    'title' => esc_html__('Order status if "ПометкаУдаления" = "true":', 'itgalaxy-woocommerce-1c'),
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                    'fieldsetEnd' => true,
                ],
                'handle_get_order_product_set_change' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Handle changes in the set of products', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, when exchanging with 1C, then the site will accept and process changes in '
                        . 'the set of products of the order when 1C sends this data (Add, remove, quantity, '
                        . 'price). Changes apply only if the product / variation on the site has guid from '
                        . '1C.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
