<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\DocumentContragentData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\DocumentDiscountData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\DocumentProductsData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\DocumentRequisitesData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\DocumentTaxData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\OrderDocumentMainData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataGenerators\TypeSaleModeQuery\PaymentDocumentMainData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\PrettyXmlFileContentResponse;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\XmlContentResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SaleModeQuery
{
    /**
     * @return void
     */
    public static function process()
    {
        // if exchange order not enabled
        if (SettingsHelper::isEmpty('send_orders')) {
            self::notEnabled();
        }

        $xml = self::getStartedXmlObject();
        $orders = self::getOrders();

        Logger::log('count orders', count($orders));
        Logger::log('list order ids', $orders);

        if (count($orders) > 0) {
            foreach ($orders as $orderID) {
                $order = \wc_get_order($orderID);

                if (!$order) {
                    Logger::log('wrong order', $orderID);

                    continue;
                }

                if (self::resolveVersion() === '3.1') {
                    $container = $xml->addChild('Контейнер');
                    $document = $container->addChild('Документ');
                } else {
                    $document = $xml->addChild('Документ');
                }

                OrderDocumentMainData::generate($document, $order);
                DocumentDiscountData::generate($document, $order);
                DocumentTaxData::generate($document, $order);
                DocumentContragentData::generate($document, $order);
                DocumentProductsData::generate($document, $order);
                DocumentRequisitesData::generate($document, $order);

                /**
                 * Эквайринговая операция.
                 *
                 * If the upload is configured and the option with the document is selected.
                 */
                if (
                    self::unloadPaymentByOrder($order)
                    && SettingsHelper::get('send_orders_unload_payments_type') !== 'main_document_requisites'
                ) {
                    if (SettingsHelper::get('send_orders_unload_payments_type') === 'subordinate_document') {
                        $paymentDocument = $document->addChild('ПодчиненныеДокументы')->addChild('ПодчиненныйДокумент');
                    } elseif (isset($container)) {
                        $paymentDocument = $container->addChild('Документ');
                    } else {
                        $paymentDocument = $xml->addChild('Документ');
                    }

                    PaymentDocumentMainData::generate($paymentDocument, $order);
                    DocumentDiscountData::generate($paymentDocument, $order);
                    DocumentTaxData::generate($document, $order);
                    DocumentContragentData::generate($paymentDocument, $order);
                    DocumentProductsData::generate($paymentDocument, $order);
                    DocumentRequisitesData::generate($paymentDocument, $order);
                }
            }
        }

        self::sendResponse($xml);

        Logger::log('order query send result');
        Logger::saveLastResponseInfo('orders content');

        // with 3.1 - 1c maybe not send request success
        // https://dev.1c-bitrix.ru/api_help/sale/algorithms/doc_from_site.php
        // ignore manual query
        if (!isset($_GET['manual-1c-import']) && self::resolveVersion() === '3.1') {
            $sentOrderTime = !empty($_SESSION['sentOrderTime']) ? $_SESSION['sentOrderTime'] : \date_i18n('Y-m-d H:i');

            SettingsHelper::$data['send_orders_last_success_export'] = str_replace(' ', 'T', $sentOrderTime);
            SettingsHelper::save(SettingsHelper::$data);

            Logger::log('setting `send_orders_last_success_export` set', [$sentOrderTime]);
        }
    }

    /**
     * @return bool
     */
    public static function hasNewOrders(): bool
    {
        return !empty(self::getOrders(false));
    }

    /**
     * @param bool $withLog
     *
     * @return array
     */
    public static function getOrders(bool $withLog = true): array
    {
        $lastTime = !SettingsHelper::isEmpty('send_orders_last_success_export')
            ? date_i18n('Y-m-d H:i:s', strtotime(SettingsHelper::get('send_orders_last_success_export')))
            : date_i18n('Y-m-d H:i:s');

        if ($withLog) {
            Logger::log('start orders modified date', $lastTime);
        }

        $excludeOrdersWithStatus = SettingsHelper::get('send_orders_exclude_if_status', []);

        if ($withLog && $excludeOrdersWithStatus) {
            Logger::log('setting - send_orders_exclude_if_status', $excludeOrdersWithStatus);
        }

        $statuses = [];

        foreach (\wc_get_order_statuses() as $status => $_) {
            if (in_array(str_replace('wc-', '', $status), $excludeOrdersWithStatus, true)) {
                continue;
            }

            $statuses[] = $status;
        }

        $getOrderListArgs = [
            'type' => 'shop_order',
            'status' => $statuses,
            'limit' => -1,
            'orderby' => 'modified',
            'order' => 'ASC',
            'return' => 'ids',
            'date_query' => [
                [
                    'column' => 'post_modified',
                    'after' => $lastTime,
                    'inclusive' => true,
                ],
            ],
        ];

        if (!SettingsHelper::isEmpty('send_orders_date_create_start')) {
            $startOrderCreateDate = \date_i18n('Y-m-d H:i:s', strtotime(SettingsHelper::get('send_orders_date_create_start')));

            if ($withLog) {
                Logger::log('setting - send_orders_date_create_start', $startOrderCreateDate);
            }

            $getOrderListArgs['date_query'][] = [
                'column' => 'post_date',
                'after' => $startOrderCreateDate,
                'inclusive' => true,
            ];
        }

        if (!SettingsHelper::isEmpty('send_orders_date_create_end')) {
            $endOrderCreateDate = \date_i18n('Y-m-d H:i:s', strtotime(SettingsHelper::get('send_orders_date_create_end')));

            if ($withLog) {
                Logger::log('setting - send_orders_date_create_end', $endOrderCreateDate);
            }

            $getOrderListArgs['date_query'][] = [
                'column' => 'post_date',
                'before' => $endOrderCreateDate,
                'inclusive' => true,
            ];
        }

        /**
         * Filters a set of parameters for a query to get a list of orders.
         *
         * @since 1.99.0
         *
         * @param array $getOrderListArgs
         */
        $getOrderListArgs = \apply_filters('itglx_wc1c_sale_query_get_order_list_params', $getOrderListArgs);

        /**
         * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
         */
        $orders = \wc_get_orders($getOrderListArgs);

        /**
         * We need to remember the time to use when setting the value, `send_orders_last_success_export` because
         * there can be a delay between getting the data and setting the value, which can cause the time to be written
         * with an erroneous offset.
         *
         * @see SaleModeSuccess
         */
        $_SESSION['sentOrderTime'] = \date_i18n('Y-m-d H:i');

        // if the request is not from 1C or the set is empty, then we will return it immediately
        if (is_admin() || isset($_GET['manual-1c-import']) || empty($orders)) {
            return $orders;
        }

        /**
         * The following logic is needed to prevent the process of receiving orders from hanging in the new protocol.
         *
         * After receiving orders, according to the new protocol, requests for receiving are repeated as long
         * as there was at least one order in the previous response, and only when there are no orders,
         * requests for receiving are terminated and a request for successful processing is received.
         *
         * For this reason, it is necessary to remember and exclude orders that have already been sent
         * in this exchange session.
         */
        $alreadySent = !empty($_SESSION['sentOrderList']) ? $_SESSION['sentOrderList'] : [];

        // if the set has already been sent is empty, then we will remember and return everything
        if (empty($alreadySent)) {
            $_SESSION['sentOrderList'] = $orders;

            return $orders;
        }

        $filteredOrderList = [];

        foreach ($orders as $orderID) {
            // if the order has already been sent, then we ignore
            if (in_array($orderID, $alreadySent)) {
                continue;
            }

            $alreadySent[] = $orderID;
            $filteredOrderList[] = $orderID;
        }

        $_SESSION['sentOrderList'] = $alreadySent;

        return $filteredOrderList;
    }

    /**
     * @param \WC_Order $order
     *
     * @return bool
     */
    public static function unloadPaymentByOrder(\WC_Order $order): bool
    {
        if (SettingsHelper::isEmpty('send_orders_unload_payments_acquiring')) {
            return false;
        }

        if (
            SettingsHelper::isEmpty('send_orders_status_acquiring')
            && SettingsHelper::isEmpty('send_orders_payment_method_acquiring')
        ) {
            return false;
        }

        $paymentGateway = \wc_get_payment_gateway_by_order($order);

        if (!$paymentGateway && !SettingsHelper::isEmpty('send_orders_payment_method_acquiring')) {
            return false;
        }

        if (
            !SettingsHelper::isEmpty('send_orders_status_acquiring')
            && !SettingsHelper::isEmpty('send_orders_payment_method_acquiring')
        ) {
            $status = in_array($order->get_status(), SettingsHelper::get('send_orders_status_acquiring'), true);
            $gateway = in_array($paymentGateway->id, SettingsHelper::get('send_orders_payment_method_acquiring'), true);

            return $status && $gateway;
        }

        if (!SettingsHelper::isEmpty('send_orders_status_acquiring')) {
            return in_array($order->get_status(), SettingsHelper::get('send_orders_status_acquiring'), true);
        }

        return in_array($paymentGateway->id, SettingsHelper::get('send_orders_payment_method_acquiring'), true);
    }

    /**
     * @param \SimpleXMLElement $xml
     *
     * @return void
     */
    private static function sendResponse(\SimpleXMLElement $xml): void
    {
        if (!empty($_GET['download'])) {
            PrettyXmlFileContentResponse::getInstance()
                ->send(
                    $xml,
                    SettingsHelper::get('send_orders_response_encoding', 'windows-1251')
                )
            ;
        } else {
            XmlContentResponse::getInstance()
                ->send(
                    $xml,
                    SettingsHelper::get('send_orders_response_encoding', 'windows-1251')
                )
            ;
        }
    }

    /**
     * @return void
     */
    private static function notEnabled(): void
    {
        self::sendResponse(self::getStartedXmlObject());

        Logger::log('order unload not enabled');
        Logger::saveLastResponseInfo('empty orders content');
        Logger::endProcessingRequestLogProtocolEntry();

        exit;
    }

    /**
     * @return \SimpleXMLElement
     */
    private static function getStartedXmlObject(): \SimpleXMLElement
    {
        $version = self::resolveVersion();

        $dom = new \DOMDocument();
        $dom->loadXML("<?xml version='1.0' encoding='utf-8'?><КоммерческаяИнформация></КоммерческаяИнформация>");
        $xml = simplexml_import_dom($dom);
        unset($dom);

        $xml->addAttribute('ВерсияСхемы', $version);
        $xml->addAttribute('ДатаФормирования', date('Y-m-d H:i', current_time('timestamp', 0)));

        Logger::log('response scheme version - ' . $version);

        return $xml;
    }

    /**
     * @return string
     */
    private static function resolveVersion(): string
    {
        if (!SettingsHelper::isEmpty('send_orders_use_scheme31')) {
            return '3.1';
        }

        $version = '2.05';

        if (isset($_SESSION['version']) && (float) $_SESSION['version'] > 2.08) {
            $version = '2.08';
        }

        return $version;
    }
}
