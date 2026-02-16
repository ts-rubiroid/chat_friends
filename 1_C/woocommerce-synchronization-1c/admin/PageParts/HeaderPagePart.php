<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Themes\OurStore;

class HeaderPagePart
{
    public static function render()
    {
        OurStore::pageHeader(
            esc_html__('Sync settings with 1C', 'itgalaxy-woocommerce-1c'),
            esc_html__('Here you can see all the settings available in the admin panel. Also, in the block below you can download the latest files from the exchange, and at the bottom of the settings page you can download the exchange logs.', 'itgalaxy-woocommerce-1c'),
            [
                [
                    'type' => 'button-link',
                    'classes' => ['btn-light'],
                    'text' => '<i class="icon-svg icon-svg-question"></i> ' . esc_html__('Documentation', 'itgalaxy-woocommerce-1c'),
                    'attributes' => [
                        'title' => esc_html__('Documentation', 'itgalaxy-woocommerce-1c'),
                        'href' => 'https://itgalaxy.company/software/wordpress-woocommerce-1c-%d0%bf%d1%80%d0%b5%d0%b4%d0%bf%d1%80'
                            . '%d0%b8%d1%8f%d1%82%d0%b8%d0%b5-%d0%be%d0%b1%d0%bc%d0%b5%d0%bd-%d0%b4%d0%b0%d0%bd%d0%bd%d1%8b%d0'
                            . '%bc%d0%b8/woocommerce-1c%d0%bf%d1%80%d0%b5%d0%b4%d0%bf%d1%80%d0%b8%d1%8f%d1%82%d0%b8%d0%b5-%d0'
                            . '%be%d0%b1%d0%bc%d0%b5%d0%bd-%d0%b4%d0%b0%d0%bd%d0%bd%d1%8b%d0%bc%d0%b8-%d0%b8%d0%bd%d1%81/',
                        'target' => '_blank',
                    ],
                ],
                [
                    'type' => 'button-link',
                    'classes' => ['btn-light'],
                    'text' => '<i class="icon-svg icon-svg-newspaper"></i> ' . esc_html__('Articles', 'itgalaxy-woocommerce-1c'),
                    'attributes' => [
                        'title' => esc_html__('Articles', 'itgalaxy-woocommerce-1c'),
                        'href' => 'https://itgalaxy.company/category/%d0%be%d0%b1%d0%bc%d0%b5%d0%bd-%d1%81-1%d1%81/',
                        'target' => '_blank',
                    ],
                ],
                [
                    'type' => 'button-link',
                    'classes' => ['btn-light'],
                    'text' => '<i class="icon-svg icon-svg-support"></i> ' . esc_html__('Support', 'itgalaxy-woocommerce-1c'),
                    'attributes' => [
                        'title' => esc_html__('Support', 'itgalaxy-woocommerce-1c'),
                        'href' => 'https://plugins.itgalaxy.company/product/woocommerce-1c-data-exchange-woocommerce-1c-obmen-dannymi/#tab-support_tab',
                        'target' => '_blank',
                    ],
                ],
            ]
        );

        self::showNotices();
    }

    private static function showNotices()
    {
        if (isset($_GET['updated'])) {
            OurStore::callout(
                esc_html__('Settings have been saved.', 'itgalaxy-woocommerce-1c'),
                'success'
            );
        }

        // check extensions end show notices
        CheckPhpExtensionNotice::render();

        // check exists exchange entry point file
        CheckExistsExchangeEntryPointFile::render();

        // maybe column was not added when activating the plugin
        CheckExistsWooCommerceAttributesTableColumn::render();

        // check loaded updater
        CheckExistsUpdater::render();
    }
}
