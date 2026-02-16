<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RootProcessStarter;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SaleModeImport
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

        SaleModeFile::processingFile();

        // clean previous getting order file after processing
        if (!SettingsHelper::isEmpty('not_delete_exchange_files')) {
            Logger::log(
                'setting `not_delete_exchange_files` is enabled, order file not deleted',
                [basename(RootProcessStarter::getCurrentExchangeFileAbsPath())]
            );
        } else {
            unlink(RootProcessStarter::getCurrentExchangeFileAbsPath());

            Logger::log(
                'order file deleted after processing',
                [basename(RootProcessStarter::getCurrentExchangeFileAbsPath())]
            );
        }

        SuccessResponse::getInstance()->send();
    }
}
