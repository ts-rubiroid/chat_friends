<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ZipHelper;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RootProcessStarter;

class CatalogModeFile
{
    public static function process()
    {
        $absFilePath = RootProcessStarter::getCurrentExchangeFileAbsPath();
        $data = file_get_contents('php://input');

        if ($data === false) {
            throw new ProtocolException(esc_html__('Error reading http stream!', 'itgalaxy-woocommerce-1c'));
        }

        if (
            !is_writable(dirname($absFilePath))
            || (file_exists($absFilePath) && !is_writable($absFilePath))
        ) {
            throw new ProtocolException(
                esc_html__('The directory / file is not writable', 'itgalaxy-woocommerce-1c')
                . ': '
                . basename($absFilePath)
            );
        }

        $fp = fopen($absFilePath, 'ab');
        $result = fwrite($fp, $data);

        if ($result !== mb_strlen($data, 'latin1')) {
            throw new ProtocolException(esc_html__('Error writing file!', 'itgalaxy-woocommerce-1c'));
        }

        if (ZipHelper::isUseZip()) {
            $_SESSION['IMPORT_1C']['zip_file'] = $absFilePath;
        }

        SuccessResponse::getInstance()->send(
            'current size - '
            . round((float) filesize($absFilePath) / MB_IN_BYTES, 3)
            . ' MB'
        );
    }
}
