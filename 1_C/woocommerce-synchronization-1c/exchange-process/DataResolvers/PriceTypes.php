<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers;

use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Parsing and save global info by price types.
 *
 * Example xml structure (position ПакетПредложений -> ТипыЦен)
 *
 * ```xml
 * <ТипыЦен>
 *      <ТипЦены>
 *          <Ид>bb14a3a4-6b17-11e0-9819-e0cb4ed5eed4</Ид>
 *          <Наименование>Розничная</Наименование>
 *          <Валюта>RUB</Валюта>
 *      </ТипЦены>
 * </ТипыЦен>
 */
class PriceTypes
{
    /**
     * Main loop parsing.
     *
     * @param \XMLReader $reader
     *
     * @return void
     */
    public static function process(\XMLReader $reader)
    {
        // if processing is disabled or processing has already occurred
        if (self::isDisabled() || self::isParsed()) {
            return;
        }

        $prices = [];

        while ($reader->read()
            && !($reader->name === 'ТипыЦен'
                && $reader->nodeType === \XMLReader::END_ELEMENT)
        ) {
            if ($reader->name !== 'ТипЦены' || $reader->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }

            $element = simplexml_load_string(trim($reader->readOuterXml()));

            $prices[(string) $element->Ид] = [
                'id' => (string) $element->Ид,
                'name' => (string) $element->Наименование,
                'currency' => (string) $element->Валюта,
            ];
        }

        Logger::log('(price types) according to current data in xml', $prices);

        Logger::log(
            '(settings) current price configuration',
            [
                'rule' => SettingsHelper::get('price_work_rule', 'regular'),
                'regular' => SettingsHelper::get('price_type_1', ''),
                'sale' => SettingsHelper::get('price_type_2', ''),
            ]
        );

        if (count($prices)) {
            update_option('all_prices_types', $prices);
        }

        self::setParsed();
    }

    /**
     * Checking if the reader is in the position of data on price types.
     *
     * @param \XMLReader $reader
     *
     * @return bool
     */
    public static function isPriceTypesNode(\XMLReader $reader)
    {
        return $reader->name === 'ТипыЦен' && $reader->nodeType !== \XMLReader::END_ELEMENT;
    }

    /**
     * Checking whether the processing of prices is disabled in the settings.
     *
     * @return bool
     */
    public static function isDisabled()
    {
        return !SettingsHelper::isEmpty('skip_product_prices');
    }

    /**
     * Allows you to check if price types have already been processed or not.
     *
     * @return bool
     */
    public static function isParsed()
    {
        return isset($_SESSION['IMPORT_1C']['price_types_parse']);
    }

    /**
     * Sets the flag that price types have been processed.
     *
     * @return void
     */
    public static function setParsed()
    {
        $_SESSION['IMPORT_1C']['price_types_parse'] = true;
    }
}
