<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class PaymentDocumentMainData
{
    /**
     * @param \SimpleXMLElement $document
     * @param \WC_Order         $order
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $document, \WC_Order $order): void
    {
        $mainData = [
            'Ид' => $order->get_id() . '-payment',
            'Номер' => $order->get_id() . '-payment',
            'Дата' => $order->get_date_created()->date_i18n('Y-m-d'),
            'Время' => $order->get_date_created()->date_i18n('H:i:s'),
            'ХозОперация' => 'Эквайринговая операция',
            'Основание' => $order->get_id(),
            'НомерВерсии' => $order->get_date_modified()->date_i18n('Y-m-d H:i:s'),
            'Роль' => 'Продавец',
            'Валюта' => self::getCurrency($order),
            'Курс' => 1,
            'Сумма' => $order->get_total() - (float) $order->get_total_refunded(),
            'Комментарий' => htmlspecialchars(self::getComment($order)),
        ];

        if (SettingsHelper::isEmpty('send_orders_tax_data_from_order')) {
            $mainData['Сумма'] -= $order->get_total_tax();
        }

        /**
         * Filters the dataset by the main fields of the payment document.
         *
         * @since 1.110.0
         *
         * @param array     $mainData
         * @param \WC_Order $order
         */
        $mainData = \apply_filters('itglx/wc/1c/sale/query/payment-document-main-data', $mainData, $order);

        foreach ($mainData as $key => $value) {
            if ((string) $value === '') {
                continue;
            }

            $document->addChild($key, $value);
        }
    }

    /**
     * @param \WC_Order $order
     *
     * @return string
     */
    private static function getCurrency(\WC_Order $order): string
    {
        if (!SettingsHelper::isEmpty('send_orders_set_currency_by_order_data')) {
            return $order->get_currency();
        }

        $basePriceType = SettingsHelper::get('price_type_1', '');
        $allPriceTypes = \get_option('all_prices_types', []);

        // if empty, then use the first
        if (empty($basePriceType) && $allPriceTypes) {
            $value = reset($allPriceTypes);
            $basePriceType = $value['id'];
        }

        if (!empty($basePriceType) && !empty($allPriceTypes[$basePriceType])) {
            return $allPriceTypes[$basePriceType]['currency'];
        }

        return 'руб';
    }

    /**
     * @param \WC_Order $order
     *
     * @return string
     */
    private static function getComment(\WC_Order $order): string
    {
        /**
         * Filters the content of the payment document comment value.
         *
         * @since 1.110.0
         *
         * @param string    $commentContent Customer comment.
         * @param \WC_Order $order
         */
        return \apply_filters('itglx/wc/1c/sale/query/payment-document-comment', $order->get_customer_note(), $order);
    }
}
