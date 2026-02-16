<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Themes\OurStore;

class CheckPhpExtensionNotice
{
    public static function render()
    {
        // check exists php-xmlreader extension
        if (!class_exists('\\XMLReader')) {
            OurStore::callout(
                sprintf(
                    '<strong>%1$s</strong>: %2$s',
                    esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
                    sprintf(
                        esc_html__(
                            'There is no extension "%1$s", without it, the exchange will not work. '
                            . 'Please install / activate the extension.',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'php-xmlreader'
                    )
                ),
                'danger'
            );
        }

        // check exists php-xml extension
        if (!function_exists('\\simplexml_load_string')) {
            OurStore::callout(
                sprintf(
                    '<strong>%1$s</strong>: %2$s',
                    esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
                    sprintf(
                        esc_html__(
                            'There is no extension "%1$s", without it, the exchange will not work. '
                            . 'Please install / activate the extension.',
                            'itgalaxy-woocommerce-1c'
                        ),
                        'php-xml'
                    )
                ),
                'danger'
            );
        }

        // check exists php-zip extension
        if (!function_exists('zip_open')) {
            OurStore::callout(
                sprintf(
                    '<strong>%1$s</strong>: %2$s',
                    esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
                    esc_html__(
                        'There is no extension "php-zip", so the exchange in the archive will not work. '
                        . 'Please install / activate the extension.',
                        'itgalaxy-woocommerce-1c'
                    )
                ),
                'danger'
            );
        }
    }
}
