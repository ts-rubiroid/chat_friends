<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\RawContentResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class SaleModeInit
{
    public static function process()
    {
        if (isset($_GET['version'])) {
            $_SESSION['version'] = $_GET['version'];
        }

        if (!is_dir(Helper::getTempPath())) {
            throw new ProtocolException(esc_html__('Initialization Error!', 'itgalaxy-woocommerce-1c'));
        }

        if (isset($_SESSION['version'])) {
            RawContentResponse::getInstance()->send("zip=no\nfile_limit=10000000\nsessid=\nversion=2.08");
        } else {
            RawContentResponse::getInstance()->send("zip=no\nfile_limit=10000000");
        }

        Logger::log('zip=no, file_limit=10000000');
        Logger::saveLastResponseInfo('parameters');
    }
}
