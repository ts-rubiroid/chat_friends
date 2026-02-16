<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\Response;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Response if processing has not yet been completed.
 *
 * @see https://dev.1c-bitrix.ru/api_help/sale/algorithms/data_2_site.php
 */
class ProgressResponse extends Response
{
    public function getType(): string
    {
        return 'progress';
    }

    public function send($message = '', $data = []): void
    {
        Logger::log('[' . $this->getType() . ']' . ($message ? ' - ' . $message : ''), $data);
        parent::send($message);
    }
}
