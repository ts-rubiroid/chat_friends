<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\Response;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class PrettyXmlFileContentResponse extends Response
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

        header(
            'Content-Disposition: attachment; filename="'
            . 'orders_('
            . Bootstrap::PLUGIN_VERSION
            . ')_'
            . date('Y-m-d_H:i:s')
            . '.xml"'
        );

        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($message->asXML());

        if ($encoding === 'utf-8') {
            header('Content-Type: text/xml; charset=utf-8');

            echo $xmlDocument->saveXML();
            // escape ok
        } else {
            header('Content-Type: text/xml; charset=windows-1251');

            echo mb_convert_encoding(
                str_replace('encoding="utf-8"', 'encoding="windows-1251"', $xmlDocument->saveXML()),
                'cp1251',
                'utf-8'
            );
            // escape ok
        }
    }
}
