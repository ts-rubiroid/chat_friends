<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing\SaleModeQuery;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class DocumentRequisitesData
{
    /**
     * @var \WC_Order
     */
    private static $order;

    /**
     * @var array
     */
    private static $requisites;

    /**
     * @param \SimpleXMLElement $document
     * @param \WC_Order         $order
     *
     * @return void
     */
    public static function generate(\SimpleXMLElement $document, \WC_Order $order): void
    {
        self::$order = $order;
        self::$requisites = [];

        self::status();
        self::payment();
        self::shipping();

        self::$requisites['ПометкаУдаления'] = self::$order->get_status() === 'cancelled' ? 'true' : 'false';
        self::$requisites['Отменен'] = self::$order->get_status() === 'cancelled' ? 'true' : 'false';

        // bitrix module
        self::$requisites['Сайт'] = \site_url();

        /**
         * Filters a set of requisites of the order document.
         *
         * @since 1.59.0
         *
         * @param array     $requisites
         * @param \WC_Order $order
         */
        self::$requisites = \apply_filters('itglx_wc1c_order_xml_requisites_data_array', self::$requisites, $order);

        if (empty(self::$requisites) || !is_array(self::$requisites)) {
            return;
        }

        $requisitesXml = $document->addChild('ЗначенияРеквизитов');

        foreach (self::$requisites as $name => $value) {
            if ($value === '') {
                continue;
            }

            $requisite = $requisitesXml->addChild('ЗначениеРеквизита');
            $requisite->addChild('Наименование', $name);
            $requisite->addChild('Значение', htmlspecialchars($value));
        }
    }

    /**
     * @return void
     */
    private static function status(): void
    {
        $orderStatus = self::$order->get_status();
        $statusMapping = SettingsHelper::get('send_orders_status_mapping', []);

        // resolve status name - maybe mapping
        if (!empty($statusMapping) && !empty($statusMapping[$orderStatus])) {
            $redefinedStatus = trim($statusMapping[$orderStatus]);

            Logger::log(
                'setting - `send_orders_status_mapping` is configured for current order status',
                [
                    self::$order->get_id(),
                    $orderStatus,
                    $redefinedStatus,
                ]
            );

            $orderStatus = $redefinedStatus;
        }

        self::$requisites['Дата изменения статуса'] = self::$order->get_date_modified()->date_i18n('Y-m-d H:i');
        self::$requisites['Статус заказа'] = $orderStatus;

        // bitrix module or extended usual module, such as UNF
        self::$requisites['Статус заказа ИД'] = $orderStatus;
        // УНФ wrong requisite name
        self::$requisites['Статуса заказа ИД'] = $orderStatus;
    }

    /**
     * @return void
     */
    private static function payment(): void
    {
        self::$requisites['Адрес плательщика'] = self::resolveAddress('billing');
        self::$requisites['Заказ оплачен'] = self::resolveIsPaidRequisiteValue();

        // for compatibility
        self::$requisites['Оплачено'] = self::$requisites['Заказ оплачен'];

        /**
         * If the upload is configured and the option with the requisites is selected.
         *
         * Required requisites for generate payment document based only order document.
         */
        if (
            SaleModeQuery::unloadPaymentByOrder(self::$order)
            && SettingsHelper::get('send_orders_unload_payments_type') === 'main_document_requisites'
        ) {
            self::$requisites['Дата оплаты'] = self::$order->get_date_paid()
                ? self::$order->get_date_paid()->date_i18n('Y-m-d')
                : self::$order->get_date_modified()->date_i18n('Y-m-d');
            self::$requisites['Номер платежного документа'] = self::$order->get_transaction_id()
                ? self::$order->get_transaction_id()
                : self::$order->get_id() . '-payment';
        }

        $paymentGateway = \wc_get_payment_gateway_by_order(self::$order);

        if (!$paymentGateway) {
            return;
        }

        $name = \wp_strip_all_tags(
            html_entity_decode(!empty($paymentGateway->title) ? $paymentGateway->title : $paymentGateway->method_title)
        );

        self::$requisites['Способ оплаты'] = $name;
        self::$requisites['Метод оплаты'] = $name;

        // bitrix module or extended usual module, such as УНФ
        self::$requisites['Метод оплаты ИД'] = $paymentGateway->id;
        self::$requisites['Способ оплаты ИД'] = $paymentGateway->id;
    }

    /**
     * @return void
     */
    private static function shipping(): void
    {
        if (!self::$order->get_shipping_method()) {
            return;
        }

        self::$requisites['Доставка разрешена'] = self::$order->get_shipping_total() > 0 ? 'true' : 'false';

        $shippingAddress = self::resolveAddress('shipping');

        self::$requisites['Адрес доставки'] = \wp_strip_all_tags(
            html_entity_decode(empty($shippingAddress) ? self::resolveAddress('billing') : $shippingAddress)
        );

        $shippingMethod = current(self::$order->get_shipping_methods());

        self::$requisites['Способ доставки'] = $shippingMethod->get_method_title();
        self::$requisites['Метод доставки'] = $shippingMethod->get_method_title();

        // bitrix module or extended usual module, such as УНФ
        self::$requisites['Способ доставки ИД'] = $shippingMethod->get_method_id();
        self::$requisites['Метод доставки ИД'] = $shippingMethod->get_method_id();

        if (method_exists($shippingMethod, 'get_instance_id')) {
            self::$requisites['Способ доставки ИД'] .= ':' . $shippingMethod->get_instance_id();
            self::$requisites['Метод доставки ИД'] .= ':' . $shippingMethod->get_instance_id();
        }
    }

    /**
     * @return string 'true' or 'false'
     */
    private static function resolveIsPaidRequisiteValue(): string
    {
        if (
            SettingsHelper::isEmpty('send_orders_status_is_paid')
            && SettingsHelper::isEmpty('send_orders_payment_method_is_paid')
        ) {
            return 'false';
        }

        $paymentGateway = \wc_get_payment_gateway_by_order(self::$order);

        if (!$paymentGateway && !SettingsHelper::isEmpty('send_orders_payment_method_is_paid')) {
            return 'false';
        }

        if (
            !SettingsHelper::isEmpty('send_orders_status_is_paid')
            && !SettingsHelper::isEmpty('send_orders_payment_method_is_paid')
        ) {
            $status = in_array(self::$order->get_status(), SettingsHelper::get('send_orders_status_is_paid'), true);
            $gateway = in_array($paymentGateway->id, SettingsHelper::get('send_orders_payment_method_is_paid'), true);

            return $status && $gateway ? 'true' : 'false';
        }

        if (!SettingsHelper::isEmpty('send_orders_status_is_paid')) {
            return in_array(self::$order->get_status(), SettingsHelper::get('send_orders_status_is_paid'), true)
                ? 'true'
                : 'false';
        }

        return in_array($paymentGateway->id, SettingsHelper::get('send_orders_payment_method_is_paid'), true)
            ? 'true'
            : 'false';
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private static function resolveAddress(string $type): string
    {
        $addressArray = self::$order->get_address($type);
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
}
