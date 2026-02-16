<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\Response;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Error response as a result of processing the exchange request.
 */
class FailureResponse extends Response
{
    public function getType(): string
    {
        return 'failure';
    }

    public function send($message = '', $error = null): void
    {
        Logger::log($this->getType() . ($message ? ' - ' . $message : ''), $error ? $error : []);
        parent::send($message);
    }
}
