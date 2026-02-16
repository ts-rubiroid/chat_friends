<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FiredSaveToChangedPriceStockSimpleProducts
{
    /**
     * @return void
     *
     * @throws ProgressException
     */
    public static function process()
    {
        if (empty($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'])) {
            return;
        }

        Logger::log(
            'fired `save` to changed price/stock simple product - start'
            . (SettingsHelper::isEmpty('offers_fired_save_simple_product_when_change_price_stock') ? ', state - only clear cache' : '')
        );

        \wp_suspend_cache_addition(true);

        $_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'] = array_unique($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts']);

        foreach ($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'] as $key => $productID) {
            if (HeartBeat::limitIsExceeded()) {
                Logger::log('fired `save` to changed price/stock simple product - progress');

                throw new ProgressException(
                    'fired `save` to changed price/stock simple product process...'
                    . count($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'])
                );
            }

            // clear cache by caching plugins
            Helper::clearCachePluginsPostCache($productID);

            if (SettingsHelper::isEmpty('offers_fired_save_simple_product_when_change_price_stock')) {
                unset($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'][$key]);

                continue;
            }

            $productObject = \wc_get_product($productID);

            if (
                $productObject
                && !is_wp_error($productObject)
                && method_exists($productObject, 'save')
            ) {
                $productObject->save();
            }

            unset($productObject, $_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts'][$key]);
        }

        \wp_suspend_cache_addition(false);

        unset($_SESSION['IMPORT_1C_PROCESS']['changedPriceStockSimpleProducts']);

        Logger::log('fired `save` to changed price/stock simple product - end');
    }
}
