<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ZipHelper;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\RawContentResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class CatalogModeInit
{
    public static function process()
    {
        Logger::log('php `memory_limit` string - ' . ini_get('memory_limit'));

        if (!is_dir(Helper::getTempPath())) {
            throw new ProtocolException(esc_html__('Initialization Error!', 'itgalaxy-woocommerce-1c'));
        }

        // clean previous exchange files
        if (!SettingsHelper::isEmpty('not_delete_exchange_files')) {
            Logger::log(
                'setting `not_delete_exchange_files` is enabled, data from the previous exchange session is not deleted'
            );
        } elseif (is_writable(Helper::getTempPath())) {
            Helper::removeDir(Helper::getTempPath());
            mkdir(Helper::getTempPath(), 0755, true);

            Logger::log('data (files) from the previous exchange session is deleted');
        }

        $zip = ZipHelper::isUseZip() ? 'yes' : 'no';

        RawContentResponse::getInstance()->send("zip={$zip}\n" . 'file_limit=' . (int) Helper::getFileSizeLimit());
        Logger::log('zip=' . $zip . ', file_limit=' . Helper::getFileSizeLimit());

        if (!isset($_SESSION['IMPORT_1C'])) {
            $_SESSION['IMPORT_1C'] = [];
        }

        if (!isset($_SESSION['IMPORT_1C_PROCESS'])) {
            $_SESSION['IMPORT_1C_PROCESS'] = [];
        }
    }
}
