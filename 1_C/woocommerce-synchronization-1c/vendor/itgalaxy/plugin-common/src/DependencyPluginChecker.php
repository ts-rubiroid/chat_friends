<?php

namespace Itgalaxy\PluginCommon;

class DependencyPluginChecker
{
    /**
     * The method facilitates the process of checking required plugins when activating your.
     *
     * If any of the plugins is not activated, the current will be deactivated and a message will be displayed to the user.
     *
     * @param string   $currentPluginFilePath
     * @param string[] $checkList
     * @param string   $noticeTitle
     *
     * @return void
     */
    public static function activateHelper($currentPluginFilePath, $checkList, $noticeTitle)
    {
        $allActivated = self::isActivated($checkList);

        if ($allActivated) {
            return;
        }

        /**
         * If at least one plugin is not activated, then we deactivate the current.
         *
         * @see https://developer.wordpress.org/reference/functions/deactivate_plugins/
         * @see https://developer.wordpress.org/reference/functions/plugin_basename/
         */
        \deactivate_plugins(\plugin_basename($currentPluginFilePath));

        /**
         * It is also necessary to display the problem message to the user.
         */
        \wp_die(
            sprintf(
                '%1$s: <strong>%2$s</strong>',
                \esc_html__(
                    'For the plugin to work, you first need to install and activate the following plugins',
                    'itgalaxy-plugin-common'
                ),
                self::resolvePluginsNameByPath($checkList)
            ),
            $noticeTitle,
            [
                'back_link' => true,
            ]
        );
        // Escape ok
    }

    /**
     * @param string   $noticeTitle
     * @param string[] $list        List of plugins. For example - ['woocommerce/woocommerce.php']
     *
     * @return void
     */
    public static function showRequirementPluginsNotice($noticeTitle, $list)
    {
        new AdminNotice(
            sprintf(
                '%1$s: <strong>%2$s</strong>',
                \esc_html__(
                    'For the plugin to work, you first need to install and activate the following plugins',
                    'itgalaxy-plugin-common'
                ),
                self::resolvePluginsNameByPath($list)
            ),
            $noticeTitle
        );
    }

    /**
     * The method allows you to check if the plugin(s) is activated.
     *
     * @param string[] $list List of plugins to check. For example - ['woocommerce/woocommerce.php']
     *
     * @return bool `false` will be returned if at least one plugin from the list is not activated.
     *
     * @see https://developer.wordpress.org/reference/functions/is_plugin_active/
     */
    public static function isActivated($list)
    {
        if (!function_exists('is_plugin_active')) {
            /**
             * Require for `is_plugin_active` function.
             *
             * @psalm-suppress MissingFile
             * @psalm-suppress UndefinedConstant
             */
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ($list as $pluginPath) {
            if (!\is_plugin_active($pluginPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The method returns a concatenated list of plugin names from the provided paths.
     *
     * @param string[] $list List of plugins paths. For example - ['woocommerce/woocommerce.php']
     *
     * @return string
     */
    private static function resolvePluginsNameByPath($list)
    {
        $knownList = [
            'woocommerce/woocommerce.php' => 'WooCommerce',
            'ninja-forms/ninja-forms.php' => 'Ninja Forms',
            'contact-form-7/wp-contact-form-7.php' => 'Contact Form 7',
            'gravityforms/gravityforms.php' => 'Gravity Forms',
            'elementor-pro/elementor-pro.php' => 'Elementor Pro',
        ];

        $resultNameList = [];

        foreach ($list as $plugin) {
            $resultNameList[] = isset($knownList[$plugin]) ? $knownList[$plugin] : $plugin;
        }

        return implode(', ', $resultNameList);
    }
}
