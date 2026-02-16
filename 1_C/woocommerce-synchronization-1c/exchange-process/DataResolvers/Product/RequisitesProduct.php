<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Parsing product requisites data.
 *
 * Example xml structure (position - Товар -> ЗначенияРеквизитов -> ЗначениеРеквизита)
 *
 * <ЗначенияРеквизитов>
 *      <ЗначениеРеквизита>
 *          <Наименование>ТипНоменклатуры</Наименование>
 *          <Значение>Запас</Значение>
 *      </ЗначениеРеквизита>
 *      <ЗначениеРеквизита>
 *          <Наименование>Полное наименование</Наименование>
 *          <Значение>Стол Трансформер Сонома</Значение>
 *      </ЗначениеРеквизита>
 * </ЗначенияРеквизитов>
 *
 * Example xml structure (position - Товар -> ЗначениеРеквизита) - old/wrong variant without node "ЗначенияРеквизитов"
 *
 * <ЗначениеРеквизита>
 *     <Наименование>ТипНоменклатуры</Наименование>
 *     <Значение>Запас</Значение>
 * </ЗначениеРеквизита>
 * <ЗначениеРеквизита>
 *     <Наименование>Полное наименование</Наименование>
 *     <Значение>Стол Трансформер Сонома</Значение>
 * </ЗначениеРеквизита>
 */
class RequisitesProduct
{
    private static $ignoreRequisites = [
        'Файл',
        'ОписаниеФайла',
    ];

    private static $excludeFromAllRequisites = [
        'ОписаниеВФорматеHTML',
    ];

    /**
     * @param \SimpleXMLElement $element
     *
     * @return array List of values and names of requisites. Example: ['name' => 'value'].
     */
    public static function process($element)
    {
        $requisites = [
            'fullName' => '',
            'weight' => 0,
            'htmlPostContent' => '',
            'allRequisites' => [],
        ];

        if (isset($element->ЗначенияРеквизитов, $element->ЗначенияРеквизитов->ЗначениеРеквизита)) {
            $requisites = self::resolveMainRequisitesData($element->ЗначенияРеквизитов->ЗначениеРеквизита, $requisites);
        }
        // old/wrong variant without node "ЗначенияРеквизитов"
        elseif (isset($element->ЗначениеРеквизита)) {
            $requisites = self::resolveMainRequisitesData($element->ЗначениеРеквизита, $requisites);
        }

        return self::resolveVariantPositionData($element, $requisites);
    }

    /**
     * @param \SimpleXMLElement $elementList
     * @param array             $requisites
     *
     * @return array List of values and names of requisites. Example: ['name' => 'value'].
     */
    private static function resolveMainRequisitesData($elementList, $requisites)
    {
        foreach ($elementList as $requisite) {
            $requisiteName = trim((string) $requisite->Наименование);

            /*
            * ignore requisite as this is useless information
            * example xml
            *
            <ЗначениеРеквизита>
               <Наименование>ОписаниеФайла</Наименование>
               <Значение>import_files/dd/ddd52f065b2511ea2c8cfa163e1c47cc_1ceaf6265b3f11ea2c8cfa163e1c47cc.jpg#569</Значение>
            </ЗначениеРеквизита>
            */
            if (in_array($requisiteName, self::$ignoreRequisites, true)) {
                if ($requisiteName === 'Файл' && !SettingsHelper::isEmpty('use_separate_file_with_html_description')) {
                    $requisites = self::resolveDescriptionInSeparateHtmlFile((string) $requisite->Значение, $requisites);
                }

                continue;
            }

            if (!in_array($requisiteName, self::$excludeFromAllRequisites, true)) {
                $requisites['allRequisites'][$requisiteName] = (string) $requisite->Значение;
            }

            switch ($requisiteName) {
                case 'Полное наименование':
                case 'Повне найменування': // requisite name in Ukrainian configurations
                    $fullName = (string) $requisite->Значение;

                    if (!empty($fullName) && !SettingsHelper::isEmpty('product_use_full_name')) {
                        $requisites['fullName'] = $fullName;
                    }

                    break;
                case 'ОписаниеВФорматеHTML':
                    $htmlPostContent = html_entity_decode((string) $requisite->Значение);

                    if (!empty($htmlPostContent) && !SettingsHelper::isEmpty('use_html_description')) {
                        $requisites['htmlPostContent'] = $htmlPostContent;
                    }

                    break;
                case 'Вес':
                    $weight = Helper::toFloat($requisite->Значение);

                    if ($weight > 0) {
                        $requisites['weight'] = $weight;
                    }

                    break;
                case 'Длина':
                    $value = Helper::toFloat($requisite->Значение);

                    if ($value > 0) {
                        $requisites['length'] = $value;
                    }

                    break;
                case 'Ширина':
                    $value = Helper::toFloat($requisite->Значение);

                    if ($value > 0) {
                        $requisites['width'] = $value;
                    }

                    break;
                case 'Высота':
                    $value = Helper::toFloat($requisite->Значение);

                    if ($value > 0) {
                        $requisites['height'] = $value;
                    }

                    break;
                default:
                    // Nothing
                    break;
            }
        }

        return $requisites;
    }

    /**
     * @param string $filePath   The relative path to the file inside the temporary directory.
     * @param array  $requisites
     *
     * @return array
     */
    private static function resolveDescriptionInSeparateHtmlFile($filePath, $requisites)
    {
        if (!empty($requisites['htmlPostContent'])) {
            return $requisites;
        }

        $basename = explode('.', basename($filePath));

        /**
         * Filters the list of file extensions, content that can be used for description.
         *
         * @since 1.82.1
         *
         * @param string[] $extensionList A list of file extensions that can contain a description. Default: ['html']
         */
        $extensionList = \apply_filters('itglx_wc1c_extension_separate_file_with_product_description', ['html']);

        if (empty($basename[1]) || !in_array($basename[1], $extensionList, true)) {
            return $requisites;
        }

        if (!file_exists(Helper::getTempPath() . '/' . $filePath)) {
            return $requisites;
        }

        $requisites['htmlPostContent'] = file_get_contents(Helper::getTempPath() . '/' . $filePath);

        return $requisites;
    }

    /**
     * Resolve xml position variant - Товар -> Реквизит
     *
     * Example xml structure
     *
     * ```xml
     * <Товар>
     *    ....
     *    <Длина>1</Длина>
     *    <Ширина>1</Ширина>
     *    <Высота>1</Высота>
     *    <Вес>1</Вес>
     *    ....
     * </Товар>
     *
     * @param \SimpleXMLElement $element
     * @param array             $requisites
     *
     * @return array List of values and names of requisites. Example: ['name' => 'value'].
     */
    private static function resolveVariantPositionData($element, $requisites)
    {
        $resolveArray = [
            'weight' => 'Вес',
            'length' => 'Длина',
            'width' => 'Ширина',
            'height' => 'Высота',
        ];

        foreach ($resolveArray as $requisitesArrayKey => $xmlNodeName) {
            if (
                !empty($requisites[$requisitesArrayKey])
                || !isset($element->{$xmlNodeName})
            ) {
                continue;
            }

            $value = Helper::toFloat($element->{$xmlNodeName});

            if ($value <= 0) {
                continue;
            }

            $requisites[$requisitesArrayKey] = $value;
        }

        return $requisites;
    }
}
