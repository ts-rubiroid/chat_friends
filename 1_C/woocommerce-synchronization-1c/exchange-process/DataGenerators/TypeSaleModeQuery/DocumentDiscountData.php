<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery;

class DocumentDiscountData
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

        $discountsNode = $document->addChild('Скидки');

        foreach ($list as $discount) {
            $discountNode = $discountsNode->addChild('Скидка');

            foreach ($discount as $name => $value) {
                if (is_array($value)) {
                    continue;
                }

                $discountNode->addChild(htmlspecialchars($name), htmlspecialchars($value));
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

        if ($order->get_discount_total() > 0) {
            $list[] = [
                'Наименование' => 'Скидка',
                'Сумма' => $order->get_discount_total(),
                'УчтеноВСумме' => 'true',
            ];
        }

        /**
         * Filters the list of discounts for the order document.
         *
         * @since 1.108.0
         *
         * @param array     $list
         * @param \WC_Order $order
         */
        return \apply_filters('itglx/wc/1c/sale/query/order-discount-list', $list, $order);
    }
}
