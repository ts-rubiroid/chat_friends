<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\Response;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Displaying the XML response content for 1C.
 */
class XmlContentResponse extends Response
{
    /**
     * @param \SimpleXMLElement $message
     * @param string            $encoding
     *
     * @return void
     */
    public function send($message, string $encoding = 'utf-8'): void
    {
        $this->clearBuffer();
        Logger::log('used encoding', $encoding);

        if ($encoding === 'utf-8') {
            header('Content-Type: text/xml; charset=utf-8');

            echo $message->asXML();
            // escape ok
        } else {
            header('Content-Type: text/xml; charset=windows-1251');

            echo mb_convert_encoding(
                str_replace('encoding="utf-8"', 'encoding="windows-1251"', $message->asXML()),
                'cp1251',
                'utf-8'
            );
            // escape ok
        }
    }
}
