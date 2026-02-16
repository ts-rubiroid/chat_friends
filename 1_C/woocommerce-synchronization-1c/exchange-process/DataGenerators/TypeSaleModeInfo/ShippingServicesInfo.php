<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeInfo;

class ShippingServicesInfo
{
    /**
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $xml): void
    {
        $shippingServicesList = $xml->addChild('СлужбыДоставки');

        foreach (self::getList() as $id => $name) {
            $shippingServiceElement = $shippingServicesList->addChild('Элемент');
            $shippingServiceElement->addChild('Ид', $id);
            $shippingServiceElement->addChild('Название', $name);
        }
    }

    /**
     * @return array
     */
    private static function getList(): array
    {
        $shippingServicesList = [];
        $shippingZones = \WC_Shipping_Zones::get_zones();

        foreach ($shippingZones as $shippingZone) {
            foreach ($shippingZone['shipping_methods'] as $method) {
                if (!isset($method->enabled) || $method->enabled !== 'yes') {
                    continue;
                }

                $id = $method->id;

                if (method_exists($method, 'get_instance_id')) {
                    $id .= ':' . $method->get_instance_id();
                }

                $shippingServicesList[$id] = !empty($method->title) ? $method->title : $method->method_title;
            }
        }

        return $shippingServicesList;
    }
}
