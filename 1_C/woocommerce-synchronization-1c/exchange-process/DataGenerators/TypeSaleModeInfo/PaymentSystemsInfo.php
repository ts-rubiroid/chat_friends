<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeInfo;

class PaymentSystemsInfo
{
    /**
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $xml)
    {
        $paymentSystemsList = $xml->addChild('ПлатежныеСистемы');

        foreach (self::getList() as $id => $name) {
            $paymentSystemElement = $paymentSystemsList->addChild('Элемент');
            $paymentSystemElement->addChild('Ид', $id);
            $paymentSystemElement->addChild('Название', $name);
            $paymentSystemElement->addChild('ТипОплаты', '');
        }
    }

    /**
     * @return array
     */
    private static function getList()
    {
        $paymentSystemsList = [];

        foreach (\WC()->payment_gateways->payment_gateways() as $id => $gateway) {
            if (!isset($gateway->enabled) || $gateway->enabled !== 'yes') {
                continue;
            }

            $paymentSystemsList[$id] = !empty($gateway->title) ? $gateway->title : $gateway->method_title;
        }

        return $paymentSystemsList;
    }
}
