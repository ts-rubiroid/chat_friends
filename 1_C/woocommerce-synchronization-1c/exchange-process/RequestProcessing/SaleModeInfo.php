<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeInfo\OrderStatusesInfo;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeInfo\PaymentSystemsInfo;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeInfo\ShippingServicesInfo;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\XmlContentResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SaleModeInfo
{
    /**
     * @return void
     */
    public static function process()
    {
        $dom = new \DOMDocument();
        $dom->loadXML("<?xml version='1.0' encoding='utf-8'?><Справочник></Справочник>");
        $xml = simplexml_import_dom($dom);

        OrderStatusesInfo::generate($xml);
        PaymentSystemsInfo::generate($xml);
        ShippingServicesInfo::generate($xml);

        XmlContentResponse::getInstance()->send(
            $xml,
            SettingsHelper::get('send_orders_response_encoding', 'windows-1251')
        );

        Logger::saveLastResponseInfo('result content');
        Logger::log('info query send result');
    }
}
