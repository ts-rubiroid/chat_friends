<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class OrderDocumentMainData
{
    /**
     * @param \SimpleXMLElement $document
     * @param \WC_Order         $order
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $document, \WC_Order $order): void
    {
        $mainOrderInfo = [
            'Ид' => $order->get_id(),
            'Номер' => $order->get_id(),
            'Дата' => $order->get_date_created()->date_i18n('Y-m-d'),
            'Время' => $order->get_date_created()->date_i18n('H:i:s'),
            'ХозОперация' => 'Заказ товара',
            'НомерВерсии' => $order->get_date_modified()->date_i18n('Y-m-d H:i:s'),
            'Роль' => 'Продавец',
            'Валюта' => self::getCurrency($order),
            'Курс' => 1,
            'Сумма' => $order->get_total() - (float) $order->get_total_refunded(),
            'Комментарий' => htmlspecialchars(self::getComment($order)),
        ];

        if (SettingsHelper::isEmpty('send_orders_tax_data_from_order')) {
            $mainOrderInfo['Сумма'] -= $order->get_total_tax();
        }

        /**
         * Filters the dataset by the main fields of the order document.
         *
         * @since 1.86.0
         * @since 1.122.0 The `$order` parameter was added
         *
         * @param array     $mainOrderInfo
         * @param int       $orderID
         * @param \WC_Order $order
         */
        $mainOrderInfo = \apply_filters('itglx_wc1c_xml_order_info_custom', $mainOrderInfo, $order->get_id(), $order);

        foreach ($mainOrderInfo as $key => $mainOrderInfoValue) {
            if ((string) $mainOrderInfoValue === '') {
                continue;
            }

            $document->addChild($key, $mainOrderInfoValue);
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
         * Filters the content of the order comment value.
         *
         * @since 1.34.0
         *
         * @param string    $commentContent Customer comment.
         * @param \WC_Order $order
         */
        return \apply_filters('itglx_wc1c_xml_order_comment', $order->get_customer_note(), $order);
    }
}
