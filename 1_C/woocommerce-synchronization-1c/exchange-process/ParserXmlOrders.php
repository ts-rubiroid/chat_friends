<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class ParserXmlOrders
{
    public function __construct()
    {
        // nothing
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function parse(string $filename): bool
    {
        $reader = new \XMLReader();
        $reader->open($filename);

        $hasDocuments = false;

        if (!isset($_SESSION['ordersProcessed'])) {
            $_SESSION['ordersProcessed'] = [];
        }

        while ($reader->read()) {
            if ($reader->name !== 'Документ' || $reader->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }

            $hasDocuments = true;

            $element = $reader->readOuterXml();
            $element = simplexml_load_string(trim($element));

            if (empty($element->ХозОперация)) {
                Logger::log('empty `ХозОперация` - ignore');

                continue;
            }

            if ((string) $element->ХозОперация !== 'Заказ товара') {
                Logger::log('`ХозОперация` != `Заказ товара` - ignore', [(string) $element->Ид]);

                continue;
            }

            if (empty($element->Номер)) {
                Logger::log('empty `Номер` - ignore');

                continue;
            }

            if (in_array((string) $element->Номер, $_SESSION['ordersProcessed'])) {
                Logger::log('has already been processed - ignore - ' . (string) $element->Номер);

                unset($element);

                continue;
            }

            $_SESSION['ordersProcessed'][] = (string) $element->Номер;

            /**
             * Filters the value of the Order ID on the site.
             *
             * @since 1.100.0
             *
             * @param int               $siteOrderID
             * @param \SimpleXMLElement $element     'Документ' node object.
             */
            $siteOrderID = \apply_filters('itglx/wc/1c/handle_site_order_resolve_id', (int) $element->Номер, $element);

            $order = \wc_get_order((int) $siteOrderID);

            if (!$order) {
                Logger::log('not exist order by `Номер` - ' . (string) $element->Номер);

                continue;
            }

            Logger::log('exist order by `Номер` - ' . (string) $element->Номер);

            if (!SettingsHelper::isEmpty('handle_get_order_product_set_change')) {
                Logger::log('apply changes set of products - ' . (string) $element->Номер);

                $this->applyProductChanges($order, $this->resolveXmlProductData($element));
            }

            if (SettingsHelper::isEmpty('handle_get_order_status_change')) {
                continue;
            }

            $requisites = [];

            if (isset($element->Номер1С)) {
                $requisites['Номер по 1С'] = (string) $element->Номер1С;
            }

            if (isset($element->ЗначенияРеквизитов, $element->ЗначенияРеквизитов->ЗначениеРеквизита)) {
                foreach ($element->ЗначенияРеквизитов->ЗначениеРеквизита as $requisite) {
                    $requisites[trim((string) $requisite->Наименование)] = (string) $requisite->Значение;
                }
            }
            // old/wrong variant without node "ЗначенияРеквизитов"
            elseif (isset($element->ЗначениеРеквизита)) {
                foreach ($element->ЗначениеРеквизита as $requisite) {
                    $requisites[trim((string) $requisite->Наименование)] = (string) $requisite->Значение;
                }
            }

            $requisites = $this->fixEmptyDateRequisites($requisites);

            if ($order->get_meta('_itgxl_wc1c_order_requisites', true) != $requisites) {
                $order->update_meta_data('_itgxl_wc1c_order_requisites', $requisites);
                $order->save_meta_data();
            }

            $resultStatus = $this->resolveResultStatus($requisites, $element, $order);

            if (empty($resultStatus)) {
                Logger::log('empty result status - ignore', [(string) $element->Ид]);

                continue;
            }

            if ($order->get_status() === $resultStatus) {
                Logger::log(
                    'current status = result status - ignore',
                    [$order->get_status(), (string) $element->Ид]
                );

                continue;
            }

            Logger::log(
                'change order status',
                [$order->get_status(), $resultStatus, (string) $element->Ид]
            );

            $order->update_status(
                $resultStatus,
                esc_html__('Order status changed through 1C, order id - ', 'itgalaxy-woocommerce-1c')
                . (string) $element->Ид
                . (isset($requisites['Номер по 1С']) ? ' / ' . $requisites['Номер по 1С'] : '')
            );
        }

        if (!$hasDocuments) {
            Logger::log('no documents (items with tag <Документ>) to processing');
        }

        return true;
    }

    /**
     * @param \SimpleXMLElement $element
     *
     * @return array
     */
    private function resolveXmlProductData(\SimpleXMLElement $element): array
    {
        $products = [];

        foreach ($element->Товары->Товар as $product) {
            $products[(string) $product->Ид] = [
                'qty' => (float) $product->Количество,
                'total' => (float) $product->Сумма,
            ];
        }

        return $products;
    }

    /**
     * @param \WC_Order $order
     * @param array     $current1CData
     *
     * @return void
     */
    private function applyProductChanges(\WC_Order $order, array $current1CData): void
    {
        if (empty($current1CData)) {
            Logger::log('empty product data, ignore apply changes set of products - ' . $order->get_id());

            return;
        }

        foreach ($order->get_items() as $item) {
            $guid = get_post_meta($item['variation_id'] ?: $item['product_id'], '_id_1c', true);

            if ($guid) {
                if (!isset($current1CData[$guid])) {
                    $order->remove_item($item->get_id());

                    unset($item);

                    continue;
                }

                $item->set_quantity($current1CData[$guid]['qty']);
                $item->set_total($current1CData[$guid]['total']);
                $item->set_subtotal($current1CData[$guid]['total']);
                $item->save();

                unset($current1CData[$guid]);
            }
        }

        if (!empty($current1CData)) {
            foreach ($current1CData as $guid => $itemData) {
                $parseID = explode('#', (string) $guid);

                // is variation
                if (!empty($parseID[1])) {
                    $elementID = ProductVariation::getIdByMeta($guid, '_id_1c');
                } else {
                    $elementID = Product::getProductIdByMeta($guid);
                }

                if (!$elementID) {
                    continue;
                }

                $product = wc_get_product($elementID);

                // must be a valid WC_Product
                if (!is_object($product)) {
                    continue;
                }

                $item = new \WC_Order_Item_Product();
                $item->set_product($product);
                $item->set_order_id($order->get_id());

                if ($product->get_type() === 'variable') {
                    $item->set_variation_id($elementID);
                }

                $item->set_quantity($itemData['qty']);
                $item->set_total($itemData['total']);
                $item->set_subtotal($itemData['total']);

                $order->add_item($item);
            }
        }

        $order->calculate_totals(true);
        $order->save();
    }

    /**
     * @param array             $requisites
     * @param \SimpleXMLElement $element
     * @param \WC_Order         $order
     *
     * @return string
     */
    private function resolveResultStatus(array $requisites, \SimpleXMLElement $element, \WC_Order $order): string
    {
        $isPaid = $this->isPaid($requisites);
        $isShipped = $this->isShipped($requisites);
        $resultStatus = '';

        if (!SettingsHelper::isEmpty('handle_get_order_status_change_if_paid') && $isPaid) {
            Logger::log('order is paid', [(string) $element->Ид]);
            $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_paid');
        }

        if (!SettingsHelper::isEmpty('handle_get_order_status_change_if_shipped') && $isShipped) {
            Logger::log('order is shipped', [(string) $element->Ид]);
            $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_shipped');
        }

        if (!SettingsHelper::isEmpty('handle_get_order_status_change_if_paid_and_shipped') && $isPaid && $isShipped) {
            Logger::log('order is paid and shipped', [(string) $element->Ид]);
            $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_paid_and_shipped');
        }

        if (
            !SettingsHelper::isEmpty('handle_get_order_status_change_if_passed')
            && isset($requisites['Проведен'])
            && $requisites['Проведен'] === 'true'
        ) {
            Logger::log('order is passed', [(string) $element->Ид]);
            $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_passed');
        }

        if (!SettingsHelper::isEmpty('handle_get_order_status_change_if_cancelled')) {
            // cancellation by requisite
            if (isset($requisites['Отменен']) && $requisites['Отменен'] === 'true') {
                Logger::log('order is cancelled', [(string) $element->Ид]);

                $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_cancelled');
            }
            // cancellation by zero amount
            elseif (
                !SettingsHelper::isEmpty('handle_get_order_status_change_if_document_amount_zero')
                && isset($element->Сумма)
                && empty((int) $element->Сумма)
            ) {
                Logger::log('order has zero amount, consider as cancelled', [(string) $element->Ид]);

                $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_cancelled');
            }
        }

        if (
            !SettingsHelper::isEmpty('handle_get_order_status_change_if_deleted')
            && isset($requisites['ПометкаУдаления'])
            && $requisites['ПометкаУдаления'] === 'true'
        ) {
            Logger::log('order is deleted', [(string) $element->Ид]);
            $resultStatus = SettingsHelper::get('handle_get_order_status_change_if_deleted');
        }

        /**
         * Filters the status value to be set for the order.
         *
         * It is used when processing data on orders that come back to the site.
         *
         * @since 1.94.0
         *
         * @param string            $resultStatus Current order status based on settings and data. Default: ''.
         * @param string[]          $requisites   A set of names and values of order details, in the form key => value.
         * @param \SimpleXmlElement $element      Node object `Документ`.
         * @param \WC_Order         $order
         */
        return \apply_filters('itglx_wc1c_handle_order_result_status', $resultStatus, $requisites, $element, $order);
    }

    /**
     * @param array $requisites
     *
     * @return array
     */
    private function fixEmptyDateRequisites(array $requisites): array
    {
        /*
        * Example xml structure
        *
        <ЗначениеРеквизита>
            <Наименование>Дата оплаты по 1С</Наименование>
            <Значение>T</Значение>
        </ЗначениеРеквизита>
        */
        if (!empty($requisites['Дата оплаты по 1С']) && $requisites['Дата оплаты по 1С'] === 'T') {
            $requisites['Дата оплаты по 1С'] = '';
        }

        /*
        * Example xml structure
        *
        <ЗначениеРеквизита>
            <Наименование>Дата отгрузки по 1С</Наименование>
            <Значение>T</Значение>
        </ЗначениеРеквизита>
        */
        if (!empty($requisites['Дата отгрузки по 1С']) && $requisites['Дата отгрузки по 1С'] === 'T') {
            $requisites['Дата отгрузки по 1С'] = '';
        }

        return $requisites;
    }

    /**
     * @param array $requisites
     *
     * @return bool
     */
    private function isPaid(array $requisites): bool
    {
        if (!empty($requisites['Дата оплаты по 1С'])) {
            return true;
        }

        return isset($requisites['Оплачен']) && in_array($requisites['Оплачен'], ['true', 'Да'], true);
    }

    /**
     * @param array $requisites
     *
     * @return bool
     */
    private function isShipped(array $requisites): bool
    {
        if (!empty($requisites['Дата отгрузки по 1С'])) {
            return true;
        }

        return isset($requisites['Отгружен']) && in_array($requisites['Отгружен'], ['true', 'Да'], true);
    }
}
