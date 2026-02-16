<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;

/**
 * Parsing and resolving data by unit for an product.
 */
class UnitProduct
{
    /**
     * Main logic.
     *
     * Examples xml structure (position - Товар -> БазоваяЕдиница)
     *
     * ```xml
     * <БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE"/>
     * <БазоваяЕдиница Код="796" НаименованиеПолное="Штука" МеждународноеСокращение="PCE">шт</БазоваяЕдиница>
     * <БазоваяЕдиница Код="778 " НаименованиеПолное="Упаковка">уп.</БазоваяЕдиница>
     * <БазоваяЕдиница>796 </БазоваяЕдиница>
     *
     * @param \SimpleXMLElement $element Node object "Товар".
     *
     * @return array
     */
    public static function process(\SimpleXMLElement $element)
    {
        if (!isset($element->БазоваяЕдиница)) {
            return [];
        }

        $value = trim((string) $element->БазоваяЕдиница);
        $globalUnit = self::getGlobalUnit($value);

        if ($globalUnit) {
            return $globalUnit;
        }

        return [
            'code' => (string) $element->БазоваяЕдиница['Код'],
            'nameFull' => (string) $element->БазоваяЕдиница['НаименованиеПолное'],
            'internationalAcronym' => (string) $element->БазоваяЕдиница['МеждународноеСокращение'],
            'value' => $value,
        ];
    }

    /**
     * Gets unit data by code from global units data.
     *
     * @param int|string $code Unit code. For example: 796.
     *
     * @return null|string[] Array with data or null if there is no data.
     *
     * @see Units::process()
     */
    private static function getGlobalUnit($code)
    {
        $globalUnits = get_option(Bootstrap::OPTION_UNITS_KEY, []);

        if (!empty($globalUnits) && isset($globalUnits[$code])) {
            return $globalUnits[$code];
        }

        return null;
    }
}
