<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SkipProductByXml
{
    private static $instance = false;

    private function __construct()
    {
        add_filter('itglx_wc1c_skip_product_by_xml', [$this, 'process'], 10, 2);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function process($skip, $element)
    {
        if ($skip) {
            return $skip;
        }

        if (!isset($element->Ид) || !isset($element->Наименование)) {
            Logger::log('(product) has no "Ид" or "Наименование" node - skip', [(string) $element->Ид]);

            return true;
        }

        // skip products without image
        if (
            !SettingsHelper::isEmpty('skip_products_without_photo')
            && (!isset($element->Картинка) || empty((string) $element->Картинка))
        ) {
            Logger::log(
                '(product) has no photo and `skip_products_without_photo` is enabled - skip',
                [(string) $element->Ид]
            );

            return true;
        }

        $name = trim(wp_strip_all_tags((string) $element->Наименование));

        if (empty($name)) {
            Logger::log('(product) has empty "Наименование" - skip', [(string) $element->Ид]);

            return true;
        }

        return $skip;
    }
}
