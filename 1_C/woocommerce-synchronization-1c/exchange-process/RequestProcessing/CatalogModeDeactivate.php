<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\DataDeletingOnFullExchange;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Cron;

class CatalogModeDeactivate
{
    public static function process()
    {
        if (DataDeletingOnFullExchange::isEnabled()) {
            update_option('not_clear_1c_complete', 1);

            $cron = Cron::getInstance();
            $cron->createCronDisableItems();
        }

        SuccessResponse::getInstance()->send(esc_html__('Task deactivate registered!', 'itgalaxy-woocommerce-1c'));
    }
}
