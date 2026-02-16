<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrdersTabs;

use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrders;

class SectionExchangeOrdersPaymentsTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('Unloading orders (payments)', 'itgalaxy-woocommerce-1c'),
            'id' => 'unload-orders-payments',
            'fields' => [
                'send_orders_unload_payments_acquiring' => [
                    'type' => 'checkbox',
                    'title' => esc_html__(
                        'Unload payment documents on acquiring operations',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'description' => esc_html__(
                        'If enabled, then in addition to the order document, a document with payment will be '
                        . 'uploaded. Please note that the load will '
                        . 'only work if your 1C configuration supports it. You can customize the unloading '
                        . 'conditions below.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_unload_payments_type' => [
                    'type' => 'select',
                    'title' => esc_html__('Form in data as:', 'itgalaxy-woocommerce-1c'),
                    'options' => [
                        'additional_document' => esc_html__('Additional document with "ХозОперация" = "Эквайринговая операция" (the basis of which is the order document)', 'itgalaxy-woocommerce-1c'),
                        'subordinate_document' => esc_html__('Subordinate ("ПодчиненныйДокумент") document with "ХозОперация" = "Эквайринговая операция" inside order document', 'itgalaxy-woocommerce-1c'),
                        'main_document_requisites' => esc_html__('Additional requisites "Дата оплаты" and "Номер платежного документа" in the order document', 'itgalaxy-woocommerce-1c'),
                    ],
                    'description' => esc_html__(
                        'The choice of option depends on your configuration and the exchange module used in 1C, that is, it all depends on the form in which your 1C is ready to receive data.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_status_acquiring' => [
                    'title' => esc_html__('Order statuses:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'select2',
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                    'description' => esc_html__(
                        'Select the order statuses under which to unload the document. If the setting with payment methods is filled in, then both conditions are taken into account.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_payment_method_acquiring' => [
                    'title' => esc_html__('Payment methods:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'select2',
                    'options' => SectionExchangeOrders::getPaymentGatewayList(),
                    'description' => esc_html__(
                        'Select the payment methods under which to unload the document. If the setting with statuses is filled in, then both conditions are taken into account.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
            ],
        ];
    }
}
