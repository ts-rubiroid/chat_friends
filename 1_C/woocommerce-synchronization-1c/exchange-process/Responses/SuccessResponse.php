<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\Response;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Response if the exchange request was processed successfully.
 */
class SuccessResponse extends Response
{
    public function send($message = '', $data = []): void
    {
        Logger::log($this->getType() . ($message ? ' - ' . $message : ''), $data);
        parent::send($message);
    }
}
