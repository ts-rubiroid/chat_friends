<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

class FixedSeparatedIdCharacteristicInOfferXmlData
{
    private static $instance = false;

    private function __construct()
    {
        \add_filter('itglx_wc1c_offer_xml_data', [$this, 'process']);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function process($element)
    {
        /*
       * Example xml structure
       *
       <Предложение>
            <Ид>0e3246ca-8397-11e9-a3b3-902b345d283a</Ид>
            <ИдХарактеристики>d0ad321c-5c8b-11ea-c484-2cfda17299b3</ИдХарактеристики>
            ...
        </Предложение>
       */

        if (!isset($element->ИдХарактеристики)) {
            return $element;
        }

        $parseID = explode('#', (string) $element->Ид);

        // if the data is already there
        if (!empty($parseID[1])) {
            return $element;
        }

        // form the correct format - nomenclature GUID#characteristic GUID
        $element->Ид = (string) $element->Ид . '#' . (string) $element->ИдХарактеристики;

        return $element;
    }
}
