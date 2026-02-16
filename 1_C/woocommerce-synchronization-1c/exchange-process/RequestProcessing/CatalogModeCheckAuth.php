<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class CatalogModeCheckAuth
{
    public static function process()
    {
        Logger::clearOldLogs();
        SuccessResponse::getInstance()->send(session_name() . "\n" . session_id() . "\n");
    }
}
