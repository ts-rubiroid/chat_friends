<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery;

use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class DocumentContragentData
{
    /**
     * @param \SimpleXMLElement $document
     * @param \WC_Order         $order
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $document, \WC_Order $order): void
    {
        if (!SettingsHelper::isEmpty('send_orders_do_not_generate_contragent_data')) {
            Logger::log('do not generate contragent data, order id - ' . $order->get_id());

            return;
        }

        $contragents = $document->addChild('Контрагенты');

        if (function_exists('\\itglx_wc1c_xml_order_contragent_data')) {
            \itglx_wc1c_xml_order_contragent_data($contragents, $order);

            return;
        }

        $dataset = [
            'Ид' => $order->get_customer_id(),
            'Роль' => 'Покупатель',
        ];

        // if division is enabled, and the company is indicated in the order
        if (
            !SettingsHelper::isEmpty('send_orders_division_contragent_into_ind_and_legal')
            && !empty($order->get_billing_company())
        ) {
            $dataset['Наименование'] = htmlspecialchars($order->get_billing_company());

            /**
             * Based on the presence of this node, the contact will be identified as a legal.
             * Attention! The contragent must not contain node `ПолноеНаименование`, otherwise it will be identified
             * as an individual.
             */
            $dataset['ОфициальноеНаименование'] = $dataset['Наименование'];
            $dataset['Адрес'] = [
                'Представление' => htmlspecialchars(self::resolveAddress('billing', $order)),
                'АдресноеПоле' => self::resolveAddressRegistration($order),
            ];

            $name = $order->get_billing_last_name() . ' ' . $order->get_billing_first_name();

            $dataset['Представители'] = [
                'Представитель' => [
                    [
                        'Контрагент' => [
                            'Отношение' => 'Контактное лицо',
                            'Ид' => md5($name),
                            'Наименование' => htmlspecialchars($name),
                        ],
                        // for compatibility
                        'Отношение' => 'Контактное лицо',
                        'Ид' => md5($name),
                        'Наименование' => htmlspecialchars($name),
                    ],
                ],
            ];
        } else {
            $dataset['Наименование'] = htmlspecialchars(
                $order->get_billing_last_name() . ' ' . $order->get_billing_first_name()
            );
            $dataset['ПолноеНаименование'] = $dataset['Наименование'];
            $dataset['Фамилия'] = htmlspecialchars($order->get_billing_last_name());
            $dataset['Имя'] = htmlspecialchars($order->get_billing_first_name());
            $dataset['АдресРегистрации'] = [
                'Представление' => htmlspecialchars(self::resolveAddress('billing', $order)),
                'АдресноеПоле' => self::resolveAddressRegistration($order),
            ];
        }

        $dataset['Контакты'] = ['Контакт' => []];

        if ($order->get_billing_email()) {
            $dataset['АдресЭП'] = htmlspecialchars($order->get_billing_email());
            $dataset['Контакты']['Контакт'][] = [
                'Тип' => 'Почта',
                'Значение' => $order->get_billing_email(),
            ];
        }

        if ($order->get_billing_phone()) {
            $dataset['Контакты']['Контакт'][] = [
                'Тип' => 'ТелефонРабочий',
                'Значение' => htmlspecialchars($order->get_billing_phone()),
                'Представление' => htmlspecialchars($order->get_billing_phone()),
            ];
        }

        /**
         * Filters the dataset by contragent in the order document.
         *
         * @since 1.48.0
         *
         * @param array     $dataset
         * @param \WC_Order $order
         */
        $dataset = \apply_filters('itglx_wc1c_order_xml_contragent_data_array', $dataset, $order);

        if (empty($dataset) || !is_array($dataset)) {
            return;
        }

        self::generateContragentXml($contragents->addChild('Контрагент'), $dataset);
    }

    /**
     * @param \WC_Order $order
     *
     * @return array
     */
    private static function resolveAddressRegistration(\WC_Order $order): array
    {
        $address = [];

        if (htmlspecialchars($order->get_billing_postcode())) {
            $address[] = [
                'Тип' => 'Почтовый индекс',
                'Значение' => htmlspecialchars($order->get_billing_postcode()),
            ];
        }

        if ($order->get_billing_country()) {
            $address[] = [
                'Тип' => 'Страна',
                'Значение' => htmlspecialchars(\WC()->countries->countries[$order->get_billing_country()]),
            ];
        }

        if (htmlspecialchars($order->get_billing_state())) {
            $address[] = [
                'Тип' => 'Регион',
                'Значение' => htmlspecialchars($order->get_billing_state()),
            ];
        }

        if (htmlspecialchars($order->get_billing_city())) {
            $address[] = [
                'Тип' => 'Город',
                'Значение' => htmlspecialchars($order->get_billing_city()),
            ];
        }

        if (htmlspecialchars($order->get_billing_address_1())) {
            $address[] = [
                'Тип' => 'Улица',
                'Значение' => htmlspecialchars($order->get_billing_address_1()),
            ];
        }

        return $address;
    }

    /**
     * @param string    $type
     * @param \WC_Order $order
     *
     * @return string
     */
    private static function resolveAddress($type, \WC_Order $order): string
    {
        $addressArray = $order->get_address($type);
        $combineItems = ['postcode', 'country', 'state', 'city', 'address_1', 'address_2'];
        $resultAddress = [];

        foreach ($combineItems as $addressItem) {
            if (empty($addressArray[$addressItem])) {
                continue;
            }

            switch ($addressItem) {
                case 'country':
                    $resultAddress[] = \WC()->countries->countries[$addressArray[$addressItem]];
                    break;
                default:
                    $resultAddress[] = $addressArray[$addressItem];
                    break;
            }
        }

        return implode(', ', $resultAddress);
    }

    // todo: refactor to universal
    private static function generateContragentXml($xml, $data)
    {
        foreach ($data as $name => $value) {
            if (!is_array($value)) {
                $xml->addChild($name, $value);
            } else {
                $level2 = $xml->addChild($name);

                foreach ($value as $level2name => $level2value) {
                    if (!is_array($level2value)) {
                        $level2->addChild($level2name, $level2value);
                    } else {
                        foreach ($level2value as $level3name => $level3value) {
                            if (!is_array($level3value)) {
                                $level2->addChild($level3name, $level3value);
                            } else {
                                $level3 = $level2->addChild(!is_numeric($level3name) ? $level3name : $level2name);

                                foreach ($level3value as $level4name => $level4value) {
                                    if (!is_array($level4value)) {
                                        $level3->addChild($level4name, $level4value);
                                    } else {
                                        $level4 = $level3->addChild(!is_numeric($level4name) ? $level4name : $level3name);

                                        foreach ($level4value as $level5name => $level5value) {
                                            $level4->addChild($level5name, $level5value);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
