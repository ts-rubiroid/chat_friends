<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Themes\OurStore;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;

class CheckExistsUpdater
{
    public static function render()
    {
        $code = get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY);

        if (empty($code) || class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
            return;
        }

        OurStore::callout(
            sprintf(
                '<strong>%1$s</strong>: %2$s',
                esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
                esc_html__(
                    'Not loaded `PucFactory`. Plugin updates not working.',
                    'itgalaxy-woocommerce-1c'
                )
            ),
            'danger'
        );
    }
}
