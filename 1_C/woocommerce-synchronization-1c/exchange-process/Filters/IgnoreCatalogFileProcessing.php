<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class IgnoreCatalogFileProcessing
{
    private static $instance = false;

    /**
     * Create new instance.
     *
     * @see https://developer.wordpress.org/reference/functions/add_filter/
     *
     * @return void
     */
    private function __construct()
    {
        if (SettingsHelper::isEmpty('ignore_catalog_file_processing')) {
            return;
        }

        \add_filter('itglx_wc1c_ignore_catalog_file_processing', [$this, 'process']);
    }

    /**
     * Returns an instance of a class or creates a new instance if it doesn't exist.
     *
     * @return IgnoreCatalogFileProcessing
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Filter callback.
     *
     * @return true
     */
    public function process()
    {
        Logger::log('setting `ignore_catalog_file_processing` is enabled');

        return true;
    }
}
