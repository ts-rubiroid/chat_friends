<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;

/**
 * Parsing offer (`Предложение`).
 */
class Offer
{
    /**
     * @param \XMLReader $reader .
     * @param float      $rate
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function process(\XMLReader $reader, $rate)
    {
        if (!HeartBeat::next('Предложение', $reader)) {
            $count = isset($_SESSION['IMPORT_1C']['heartbeat']['Предложение'])
                ? $_SESSION['IMPORT_1C']['heartbeat']['Предложение']
                : 0;

            throw new ProgressException("offers processing, node count {$count}...");
        }

        $element = simplexml_load_string(trim($reader->readOuterXml()));

        if (!$element instanceof \SimpleXMLElement) {
            return;
        }

        if (\has_action('itglx_wc1c_offer_custom_processing')) {
            /**
             * The action allows to organize custom processing of the offer.
             *
             * If an action is registered, then it is triggered for every offer.
             *
             * @since 1.95.0
             *
             * @param \SimpleXMLElement $element 'Предложение' node object.
             */
            \do_action('itglx_wc1c_offer_custom_processing', $element);

            return;
        }

        // Offers without ID do not make sense, since they cannot be associated with any product
        if (!isset($element->Ид)) {
            return;
        }

        /**
         * Filters the content of the offer object.
         *
         * If necessary, allows you to make changes to the data, before they are processed.
         *
         * @since 1.76.0
         *
         * @param \SimpleXMLElement $element
         *
         * @see FixedSeparatedIdCharacteristicInOfferXmlData
         */
        $element = \apply_filters('itglx_wc1c_offer_xml_data', $element);

        // if duplicate offer
        if (in_array((string) $element->Ид, $_SESSION['IMPORT_1C_PROCESS']['allCurrentOffers'])) {
            return;
        }

        $parseID = explode('#', (string) $element->Ид);

        // not empty variation hash
        if (!empty($parseID[1])) {
            VariationOffer::process($element, $parseID[0], $rate);
        } else {
            SimpleOffer::process($element, $rate);
        }

        $_SESSION['IMPORT_1C_PROCESS']['allCurrentOffers'][] = (string) $element->Ид;
    }

    /**
     * Checking if the reader is in the position of data on offer.
     *
     * @param \XMLReader $reader
     *
     * @return bool
     */
    public static function isOfferNode(\XMLReader $reader)
    {
        return $reader->name === 'Предложение' && $reader->nodeType === \XMLReader::ELEMENT;
    }
}
