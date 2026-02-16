<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\ParserXmlOrders;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RootProcessStarter;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SaleModeFile
{
    public static function process()
    {
        // if exchange order not enabled
        if (
            SettingsHelper::isEmpty('handle_get_order_status_change')
            && SettingsHelper::isEmpty('handle_get_order_product_set_change')
        ) {
            if (SettingsHelper::isEmpty('handle_get_order_status_change')) {
                Logger::log('handle_get_order_status_change not enabled');
            }

            if (SettingsHelper::isEmpty('handle_get_order_product_set_change')) {
                Logger::log('handle_get_order_product_set_change not enabled');
            }

            SuccessResponse::getInstance()->send();

            return;
        }

        $data = file_get_contents('php://input');

        if ($data === false) {
            throw new ProtocolException(esc_html__('Error reading http stream!', 'itgalaxy-woocommerce-1c'));
        }

        if (
            !is_writable(dirname(RootProcessStarter::getCurrentExchangeFileAbsPath()))
            || (
                file_exists(RootProcessStarter::getCurrentExchangeFileAbsPath())
                && !is_writable(RootProcessStarter::getCurrentExchangeFileAbsPath())
            )
        ) {
            throw new ProtocolException(
                esc_html__('The directory / file is not writable', 'itgalaxy-woocommerce-1c')
                . ': '
                . basename(RootProcessStarter::getCurrentExchangeFileAbsPath())
            );
        }

        $fp = fopen(RootProcessStarter::getCurrentExchangeFileAbsPath(), 'ab');
        $result = fwrite($fp, $data);

        if ($result !== mb_strlen($data, 'latin1')) {
            throw new ProtocolException(esc_html__('Error writing file!', 'itgalaxy-woocommerce-1c'));
        }

        // old modules compatible, processing without import request
        if (empty($_SESSION['version']) && self::isValidXml()) {
            self::processingFile();
        }

        SuccessResponse::getInstance()->send();
    }

    public static function processingFile()
    {
        $absFilePath = RootProcessStarter::getCurrentExchangeFileAbsPath();

        // check requested parse file exists
        if (!file_exists($absFilePath)) {
            throw new ProtocolException(esc_html('File not exists! - ' . basename($absFilePath)));
        }

        $parserXml = new ParserXmlOrders();
        $parserXml->parse($absFilePath);

        if (!\has_action('itglx_wc1c_exchange_sale_import_file_processing_completed')) {
            return;
        }

        Logger::log('(sale) has_action `itglx_wc1c_exchange_sale_import_file_processing_completed` - run');

        /**
         * Hook makes it possible to perform some of your actions when the file is processing.
         *
         * @param string $absFilePath Absolute path to the file that has been processed.
         *
         * @since 1.121.0
         */
        \do_action('itglx_wc1c_exchange_sale_import_file_processing_completed', $absFilePath);
    }

    /**
     * @return bool
     *
     * @throws \Exception
     *
     * @see https://www.php.net/manual/en/function.libxml-use-internal-errors.php
     */
    private static function isValidXml()
    {
        if (!function_exists('libxml_use_internal_errors')) {
            Logger::log('function `libxml_use_internal_errors` not exists');

            return true;
        }

        $useErrors = \libxml_use_internal_errors(true);
        \simplexml_load_file(RootProcessStarter::getCurrentExchangeFileAbsPath());

        $errors = \libxml_get_errors();

        \libxml_clear_errors();
        \libxml_use_internal_errors($useErrors);

        // if no has errors when load xml
        if (empty($errors)) {
            return true;
        }

        $messages = [];

        foreach ($errors as $error) {
            $messages[] = $error->message;
        }

        Logger::log('xml has errors - ' . basename(RootProcessStarter::getCurrentExchangeFileAbsPath()), $messages);

        return false;
    }
}
