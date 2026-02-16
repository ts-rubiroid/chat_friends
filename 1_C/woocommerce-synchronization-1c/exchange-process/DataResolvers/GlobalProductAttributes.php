<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ProductAttributeValueEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Parsing and save info by main product attributes.
 *
 * @since 1.13.0
 */
class GlobalProductAttributes
{
    /**
     * Main loop parsing.
     *
     * Example xml structure (position - Классификатор -> Свойства)
     *
     * ```xml
     * <Свойства>
     *      <СвойствоНоменклатуры>
     *          <Ид>bd9b5fd0-99c7-11ea-9e2b-00155d467e00</Ид>
     *          <Наименование>ouhu</Наименование>
     *          <Обязательное>false</Обязательное>
     *          <Множественное>false</Множественное>
     *          <ИспользованиеСвойства>true</ИспользованиеСвойства>
     *      </СвойствоНоменклатуры>
     * </Свойства>
     *
     * <Свойства>
     *      <Свойство>
     *          <Ид>65fbdca3-85d6-11da-9aea-000d884f5d77</Ид>
     *          <ПометкаУдаления>false</ПометкаУдаления>
     *          <Наименование>Модель</Наименование>
     *          <ТипЗначений>Справочник</ТипЗначений>
     *          <ВариантыЗначений>
     *              <Справочник>
     *                  <ИдЗначения>65fbdca4-85d6-11da-9aea-000d884f5d77</ИдЗначения>
     *                  <Значение>KSF 32420</Значение>
     *              </Справочник>
     *          </ВариантыЗначений>
     *      </Свойство>
     * </Свойства>
     *
     * @param \XMLReader $reader
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function process(\XMLReader $reader)
    {
        $numberOfOptions = 0;

        if (!isset($_SESSION['IMPORT_1C']['numberOfOptions'])) {
            $_SESSION['IMPORT_1C']['numberOfOptions'] = 0;
        }

        $options = \get_option('all_product_options', []);

        if (!is_array($options)) {
            $options = [];
        }

        /**
         * Filters the list of product properties to be ignored during processing.
         *
         * @since 1.74.1
         *
         * @param string[] $ignoreAttributeProcessing Array of strings with property guid to be ignored during processing.
         */
        $ignoreAttributeProcessing = \apply_filters('itglx_wc1c_attribute_ignore_guid_array', []);

        while (
            $reader->read()
            && !($reader->name === 'Свойства' && $reader->nodeType === \XMLReader::END_ELEMENT)
        ) {
            if (
                ($reader->name !== 'Свойство' && $reader->name !== 'СвойствоНоменклатуры')
                || $reader->nodeType !== \XMLReader::ELEMENT
            ) {
                continue;
            }

            if (HeartBeat::limitIsExceeded()) {
                throw new ProgressException("options processing {$_SESSION['IMPORT_1C']['numberOfOptions']}...");
            }

            ++$numberOfOptions;

            if ($numberOfOptions <= $_SESSION['IMPORT_1C']['numberOfOptions']) {
                continue;
            }

            $element = simplexml_load_string(trim($reader->readOuterXml()));

            $optionGuid = trim((string) $element->Ид);
            $optionName = trim((string) $element->Наименование);

            // Property without GUID or name is meaningless.
            if (empty($optionGuid) || empty($optionName) || $optionGuid === '00000000-0000-0000-0000-000000000000') {
                $_SESSION['IMPORT_1C']['numberOfOptions'] = $numberOfOptions;

                Logger::log('(attribute) ignore, option has no guid or name', [$optionGuid, $optionName]);

                continue;
            }

            // Maybe custom processing is registered for this property.
            if (self::customProcessing($element, $optionGuid, $optionName)) {
                $_SESSION['IMPORT_1C']['numberOfOptions'] = $numberOfOptions;

                continue;
            }

            // If the property is in the ignore list.
            if (in_array($optionGuid, $ignoreAttributeProcessing, true)) {
                $_SESSION['IMPORT_1C']['numberOfOptions'] = $numberOfOptions;

                Logger::log('(attribute) ignore, option is on ignore list', [$optionGuid]);

                continue;
            }

            $attribute = ProductAttributeEntity::get($optionGuid);

            /*
            <Свойство>
                <Ид>2fb6de87-3952-11eb-993b-14dae968aea7</Ид>
                <Наименование>Размер</Наименование>
                <ПометкаУдаления>false</ПометкаУдаления>
                <ТипЗначений>Число</ТипЗначений>
            </Свойство>
            */
            if (isset($element->ПометкаУдаления) && (string) $element->ПометкаУдаления === 'true') {
                Logger::log('(attribute) node `ПометкаУдаления` has value `true`', [$optionGuid]);

                if ($attribute) {
                    \wc_delete_attribute($attribute->attribute_id);
                    Logger::log('(attribute) deleted, id - ' . $attribute->attribute_id, [$optionGuid]);
                }

                $_SESSION['IMPORT_1C']['numberOfOptions'] = $numberOfOptions;

                continue;
            }

            if (!$attribute) {
                /**
                 * @see \Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FindAttribute
                 */
                $attribute = apply_filters('itglx_wc1c_find_exists_product_attribute', null, $element);

                if ($attribute) {
                    Logger::log(
                        '(attribute) found through filter `itglx_wc1c_find_exists_product_attribute`, `attribute_id` - '
                        . $attribute->attribute_id,
                        [$optionGuid]
                    );

                    // set the GUID if the attribute does not already have it
                    if (empty($attribute->id_1c)) {
                        ProductAttributeEntity::update(['id_1c' => $optionGuid], $attribute->attribute_id);
                    }

                    // update the data, as it may have changed during the search
                    $options = get_option('all_product_options', []);
                }
            }

            if ($attribute) {
                $attributeTaxName = 'pa_' . $attribute->attribute_name;
                $attributeUpdateFields = [];

                if (SettingsHelper::isEmpty('skip_update_attribute_label')) {
                    $attributeUpdateFields['attribute_label'] = $optionName;
                }

                if (!empty($attributeUpdateFields)) {
                    ProductAttributeEntity::update($attributeUpdateFields, $attribute->attribute_id);
                }

                Logger::log(
                    '(attribute) updated attribute by data `Свойства` - ' . $attributeTaxName,
                    [$attribute->attribute_id, $optionGuid, $attributeUpdateFields]
                );

                /*
                 * There may be a situation with the cache, when the first time the site is executed after
                 * the attribute is created, the taxonomy is still missing.
                 */
                if (!\taxonomy_exists($attributeTaxName)) {
                    Logger::log('(attribute) still not exists taxonomy, manual register - ' . $attributeTaxName);

                    \register_taxonomy($attributeTaxName, null);
                }
            } else {
                $attributeCreate = ProductAttributeEntity::insert($optionName, uniqid(), $optionGuid);

                Logger::log('(attribute) created attribute by data `Свойства`', $attributeCreate);

                $attributeTaxName = 'pa_' . $attributeCreate['attribute_name'];

                /**
                 * To use the taxonomy right after the attribute is created, we need to register it. The next time
                 * the site is loaded, the taxonomy will already be registered with the WooCommerce logic,
                 * so we only need it now.
                 *
                 * @see https://developer.wordpress.org/reference/functions/register_taxonomy/
                 */
                \register_taxonomy($attributeTaxName, null);
            }

            if (isset($element->ТипЗначений)) {
                $type = (string) $element->ТипЗначений;
            } else {
                $type = 'oldType';
            }

            $options[$optionGuid] = [
                'taxName' => $attributeTaxName,
                'createdTaxName' => isset($options[$optionGuid]['createdTaxName'])
                    ? $options[$optionGuid]['createdTaxName']
                    : $attributeTaxName,
                'type' => $type,
                'values' => [],
            ];

            /*
             * Example xml structure
             * position - Классификатор -> Свойства
             *
            <Свойства>
                <Свойство>
                    <Ид>65fbdca3-85d6-11da-9aea-000d884f5d77</Ид>
                    <ПометкаУдаления>false</ПометкаУдаления>
                    <Наименование>Модель</Наименование>
                    <ТипЗначений>Справочник</ТипЗначений>
                    <ВариантыЗначений>
                        <Справочник>
                            <ИдЗначения>65fbdca4-85d6-11da-9aea-000d884f5d77</ИдЗначения> (alt - <Ид>65fbdca4-85d6-11da-9aea-000d884f5d77</Ид>)
                            <Значение>KSF 32420</Значение>
                        </Справочник>
                    </ВариантыЗначений>
                </Свойство>
            </Свойства>
            */
            if (isset($element->ВариантыЗначений, $element->ВариантыЗначений->{$type})) {
                Logger::log('(attribute) start processing variants - ' . $attributeTaxName, $optionGuid);

                $numberOfOptionValues = 0;

                if (!isset($_SESSION['IMPORT_1C']['numberOfOptionValues'])) {
                    $_SESSION['IMPORT_1C']['numberOfOptionValues'] = 0;
                }

                if (!isset($_SESSION['IMPORT_1C']['currentOptionValues'])) {
                    $_SESSION['IMPORT_1C']['currentOptionValues'] = [];
                }

                foreach ($element->ВариантыЗначений->{$type} as $variant) {
                    if (HeartBeat::limitIsExceeded()) {
                        Logger::log('(attribute) progress processing variants - ' . $attributeTaxName, $optionGuid);

                        throw new ProgressException("option {$optionGuid} processing variants...");
                    }

                    ++$numberOfOptionValues;

                    if ($numberOfOptionValues <= $_SESSION['IMPORT_1C']['numberOfOptionValues']) {
                        continue;
                    }

                    $variantValue = (string) $variant->Значение;
                    $variantValueGuid = isset($variant->Ид) ? (string) $variant->Ид : (string) $variant->ИдЗначения;

                    /**
                     * Example xml structure.
                     *
                     * ```xml
                     * <Справочник>
                     *    <ИдЗначения/>
                     *    <Значение/>
                     * </Справочник>
                     *
                     * <Справочник>
                     *    <Ид/>
                     *    <Значение/>
                     * </Справочник>
                     */
                    if (empty($variantValue) || empty($variantValueGuid)) {
                        $_SESSION['IMPORT_1C']['numberOfOptionValues'] = $numberOfOptionValues;

                        continue;
                    }

                    $uniqId1c = md5($variantValueGuid . $options[$optionGuid]['createdTaxName']);
                    $variantTerm = Term::getTermIdByMeta($uniqId1c);

                    if (!$variantTerm) {
                        $variantTerm = Term::getTermIdByMeta($variantValueGuid);
                    }

                    if (!$variantTerm) {
                        $variantTerm = apply_filters(
                            'itglx_wc1c_find_exists_product_attribute_value_term_id',
                            0,
                            $variant,
                            $attributeTaxName
                        );

                        if ($variantTerm) {
                            Logger::log(
                                '(attribute) Found value ' . $variantValue . ' through filter '
                                . '`itglx_wc1c_find_exists_product_attribute_value_term_id`, `term_id` - '
                                . $variantTerm,
                                [$attributeTaxName, $variantValueGuid]
                            );

                            Term::update1cId($variantTerm, $uniqId1c);
                        }
                    }

                    // if exists when update
                    if ($variantTerm) {
                        ProductAttributeValueEntity::update($variantValue, $variantTerm, $attributeTaxName);
                    } else {
                        $variantTerm = ProductAttributeValueEntity::insert($variantValue, $attributeTaxName, uniqid());
                        $variantTerm = $variantTerm['term_id'];

                        // default meta value by ordering
                        update_term_meta($variantTerm, 'order_' . $attributeTaxName, 0);

                        Term::update1cId($variantTerm, $uniqId1c);
                    }

                    $_SESSION['IMPORT_1C']['currentOptionValues'][$variantValueGuid] = $variantTerm;

                    $options[$optionGuid]['values'][$variantValueGuid] = $variantTerm;
                    $_SESSION['IMPORT_1C']['numberOfOptionValues'] = $numberOfOptionValues;
                }

                Logger::log(
                    '(attribute) end processing variants - '
                    . $attributeTaxName
                    . ', count - '
                    . $_SESSION['IMPORT_1C']['numberOfOptionValues'],
                    $optionGuid
                );

                $options[$optionGuid]['values'] = $_SESSION['IMPORT_1C']['currentOptionValues'];

                // reset count resolved values
                $_SESSION['IMPORT_1C']['numberOfOptionValues'] = 0;

                // reset current resolved values
                $_SESSION['IMPORT_1C']['currentOptionValues'] = [];
            }

            if (count($options)) {
                update_option('all_product_options', $options);
            }

            $_SESSION['IMPORT_1C']['numberOfOptions'] = $numberOfOptions;

            delete_option($attributeTaxName . '_children');
        }

        if (count($options)) {
            update_option('all_product_options', $options);
        }

        self::setParsed();

        throw new ProgressException('options processing end...');
    }

    /**
     * Allows you to check if properties have already been processed or not.
     *
     * @return bool
     */
    public static function isParsed()
    {
        return isset($_SESSION['IMPORT_1C']['optionsIsParsed']);
    }

    /**
     * Sets the flag that properties have been processed.
     *
     * @return void
     */
    public static function setParsed()
    {
        $_SESSION['IMPORT_1C']['optionsIsParsed'] = true;
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string            $optionGuid
     * @param string            $optionName
     *
     * @return bool
     */
    private static function customProcessing($element, $optionGuid, $optionName): bool
    {
        if (\has_action('itglx_wc1c_global_product_option_custom_processing_' . $optionGuid)) {
            Logger::log('(attribute) option has custom processing by guid', [$optionGuid]);

            /**
             * The action allows to organize custom processing of the property.
             *
             * @since 1.86.1
             *
             * @param \SimpleXMLElement $element
             */
            \do_action('itglx_wc1c_global_product_option_custom_processing_' . $optionGuid, $element);

            return true;
        }

        if (\has_action('itglx_wc1c_global_product_option_custom_processing_' . $optionName)) {
            Logger::log('(attribute) option has custom processing by name', [$optionName]);

            /**
             * The action allows to organize custom processing of the property.
             *
             * @since 1.122.0
             *
             * @param \SimpleXMLElement $element
             */
            \do_action('itglx_wc1c_global_product_option_custom_processing_' . $optionName, $element);

            return true;
        }

        return false;
    }
}
