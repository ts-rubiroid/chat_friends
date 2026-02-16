<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class DocumentTaxData
{
    /**
     * @param \SimpleXMLElement $document
     * @param \WC_Order         $order
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $document, \WC_Order $order): void
    {
        $list = self::getList($order);

        if (empty($list)) {
            return;
        }

        $taxesNode = $document->addChild('Налоги');

        foreach ($list as $tax) {
            $taxNode = $taxesNode->addChild('Налог');

            foreach ($tax as $name => $value) {
                if (is_array($value)) {
                    continue;
                }

                $taxNode->addChild(htmlspecialchars($name), htmlspecialchars($value));
            }
        }
    }

    /**
     * @param \WC_Order $order
     *
     * @return array
     */
    private static function getList(\WC_Order $order): array
    {
        $list = [];

        if (!SettingsHelper::isEmpty('send_orders_tax_data_from_order')) {
            $list[] = [
                'Наименование' => 'НДС',
                'УчтеноВСумме' => 'true', // СуммаВключаетНДС = Истина
                'Сумма' => $order->get_total_tax(),
            ];
        }

        /**
         * Filters the list of taxes for the order document.
         *
         * @since 1.119.0
         *
         * @param array     $list
         * @param \WC_Order $order
         */
        return \apply_filters('itglx/wc/1c/sale/query/order-tax-list', $list, $order);
    }
}
