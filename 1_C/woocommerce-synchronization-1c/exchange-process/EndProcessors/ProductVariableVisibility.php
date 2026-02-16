<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class ProductVariableVisibility
{
    /**
     * @return void
     *
     * @throws ProgressException
     */
    public static function process()
    {
        if (empty($_SESSION['IMPORT_1C']['variableVisibility'])) {
            return;
        }

        if (!isset($_SESSION['IMPORT_1C']['numberOfSetVisibility'])) {
            $_SESSION['IMPORT_1C']['numberOfSetVisibility'] = 0;
        }

        $numberOfSetVisibility = 0;

        Logger::log('variable product set visibility - start');

        foreach ($_SESSION['IMPORT_1C']['variableVisibility'] as $productID => $stockStatusList) {
            if (HeartBeat::limitIsExceeded()) {
                Logger::log('variable product set visibility - progress');

                throw new ProgressException("applying visibility to variable products {$numberOfSetVisibility}...");
            }

            ++$numberOfSetVisibility;

            if ($numberOfSetVisibility <= $_SESSION['IMPORT_1C']['numberOfSetVisibility']) {
                continue;
            }

            if (in_array('instock', $stockStatusList)) {
                Product::show($productID, true);
            } elseif (in_array('onbackorder', $stockStatusList)) {
                Product::show($productID, true, 'onbackorder');
            } else {
                Product::hide($productID, true);
            }

            // clear cache by caching plugins
            Helper::clearCachePluginsPostCache($productID);

            $_SESSION['IMPORT_1C']['numberOfSetVisibility'] = $numberOfSetVisibility;
        }

        Logger::log('variable product set visibility - end');

        unset($_SESSION['IMPORT_1C']['variableVisibility']);
    }
}
