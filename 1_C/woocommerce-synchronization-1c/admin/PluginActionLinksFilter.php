<?php

namespace Itgalaxy\Wc\Exchange1c\Admin;

use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;

class PluginActionLinksFilter
{
    /**
     * @see https://developer.wordpress.org/reference/hooks/plugin_action_links/
     *
     * @return void
     */
    public function __construct()
    {
        add_filter('plugin_action_links', [$this, 'pluginActionLinks'], 10, 2);
    }

    /**
     * @param array  $actions
     * @param string $pluginFile
     *
     * @return array
     */
    public function pluginActionLinks($actions, $pluginFile)
    {
        if (strpos($pluginFile, 'woocommerce-synchronization-1c.php') === false) {
            return $actions;
        }

        $settingsLink = '<a href="'
            . admin_url()
            . 'admin.php?page=' . Bootstrap::OPTIONS_KEY . '">'
            . esc_html__('Settings', 'itgalaxy-woocommerce-1c')
            . '</a>';

        $documentationLink = '<a href="'
            . esc_url(
                __(
                    'https://itgalaxy.company/software/wordpress-woocommerce-1c-предприятие-обмен-данными/'
                    . 'woocommerce-1cпредприятие-обмен-данными-инс/',
                    'itgalaxy-woocommerce-1c'
                )
            )
            . '" target="_blank">'
            . esc_html__('Documentation', 'itgalaxy-woocommerce-1c')
            . '</a>';

        array_push(
            $actions,
            $settingsLink,
            $documentationLink
        );

        return $actions;
    }
}
