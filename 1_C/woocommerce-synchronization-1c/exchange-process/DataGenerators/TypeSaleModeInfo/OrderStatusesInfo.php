<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeInfo;

class OrderStatusesInfo
{
    /**
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $xml)
    {
        $statusList = $xml->addChild('Статусы');
        $statusList1 = $xml->addChild('Cтатусы'); // first symbol eng `C` - compatible 1c module typo

        foreach (self::getList() as $status => $label) {
            $statusElement = $statusList->addChild('Элемент');
            $statusElement1 = $statusList1->addChild('Элемент');
            $statusElement->addChild('Ид', $status);
            $statusElement1->addChild('Ид', $status);
            $statusElement->addChild('Название', esc_html($label));
            $statusElement1->addChild('Название', esc_html($label));
        }
    }

    /**
     * @return array
     */
    private static function getList()
    {
        $statusList = [];

        foreach (\wc_get_order_statuses() as $status => $label) {
            $statusList[str_replace('wc-', '', $status)] = $label;
        }

        return $statusList;
    }
}
