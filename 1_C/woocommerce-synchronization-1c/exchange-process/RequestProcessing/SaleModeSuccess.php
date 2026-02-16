<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SaleModeSuccess
{
    public static function process()
    {
        // if exchange order not enabled
        if (SettingsHelper::isEmpty('send_orders')) {
            Logger::log('order unload not enabled - ignore set setting `send_orders_last_success_export`');
        } else {
            $sentOrderTime = !empty($_SESSION['sentOrderTime']) ? $_SESSION['sentOrderTime'] : date_i18n('Y-m-d H:i');

            SettingsHelper::$data['send_orders_last_success_export'] = str_replace(' ', 'T', $sentOrderTime);
            SettingsHelper::save(SettingsHelper::$data);

            Logger::log(
                '1c send success, setting `send_orders_last_success_export` set',
                [$sentOrderTime]
            );
        }

        SuccessResponse::getInstance()->send();
    }
}
