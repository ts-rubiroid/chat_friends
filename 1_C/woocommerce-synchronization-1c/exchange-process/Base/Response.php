<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base;

use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Class Response.
 */
abstract class Response
{
    private static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Getting response type, one of the options - `success`, `progress` or `failure`.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'success';
    }

    /**
     * Displaying the response content for 1C.
     *
     * @param mixed $message Additional message text.
     *
     * @return void
     */
    public function send($message): void
    {
        $this->clearBuffer();

        echo $this->getType() . "\n" . $message;
        // escape ok

        Logger::saveLastResponseInfo($this->getType() . ($message ? ' - ' . $message : ''));
    }

    /**
     * Clearing the buffer from possible garbage before displaying a response for 1C.
     *
     * Otherwise 1C will not be able to parse the response.
     *
     * @return void
     */
    public function clearBuffer(): void
    {
        if (!\apply_filters('itglx_wc1c_clear_buffer_before_print_response_for_1C', true)) {
            return;
        }

        $bufferStatus = ob_get_status();

        // checking for an active buffer
        if (empty($bufferStatus)) {
            unset($bufferStatus);

            return;
        }

        $content = ob_get_contents();

        if ($content !== '') {
            ob_clean();

            Logger::log('output buffer cleared, content - ', [$content]);
        }

        unset($content, $bufferStatus);
    }
}
