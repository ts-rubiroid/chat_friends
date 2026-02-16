<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;

class Sale
{
    public static function process()
    {
        switch ($_GET['mode']) {
            case 'checkauth':
                SaleModeCheckAuth::process();
                break;
            case 'init':
                SaleModeInit::process();
                break;
            case 'query':
                SaleModeQuery::process();
                break;
            case 'info':
                SaleModeInfo::process();
                break;
            case 'success':
                SaleModeSuccess::process();
                break;
            case 'file':
                SaleModeFile::process();
                break;
            case 'import':
                SaleModeImport::process();
                break;
            default:
                throw new ProtocolException('unknown or empty mode');
        }
    }
}
