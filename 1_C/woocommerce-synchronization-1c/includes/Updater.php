<?php

namespace Itgalaxy\Wc\Exchange1c\Includes;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class Updater
{
    /**
     * @var int|string
     */
    private $pluginID;

    /**
     * @var string
     */
    private $option;

    /**
     * @var string
     */
    private $version;

    /**
     * Updater constructor.
     *
     * @param object $bootstrap
     */
    public function __construct($bootstrap)
    {
        $this->pluginID = $bootstrap::PLUGIN_ID;
        $this->option = $bootstrap::PURCHASE_CODE_OPTIONS_KEY;
        $this->version = $bootstrap::PLUGIN_VERSION;

        /**
         * @see https://developer.wordpress.org/reference/hooks/in_plugin_update_message-file/
         */
        \add_action('in_plugin_update_message-' . \plugin_basename($bootstrap::$plugin), [$this, 'showPluginListUpgradeNotice']);

        $this->init($bootstrap::$plugin);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function showPluginListUpgradeNotice($data)
    {
        if (!isset($data['upgrade_notice'])) {
            return;
        }

        echo \wp_kses_post($data['upgrade_notice']);
    }

    /**
     * @param string $pluginFile
     *
     * @return void
     */
    private function init($pluginFile)
    {
        $code = \get_site_option($this->option, '');

        if (empty($code) || !class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
            return;
        }

        $checker = PucFactory::buildUpdateChecker(
            'https://envato.itgalaxy.company/envato/plugin-request',
            $pluginFile,
            'woocommerce-synchronization-1c'
        );

        $checker->addQueryArgFilter(function () {
            return [
                'purchaseCode' => \get_site_option($this->option, ''),
                'itemID' => $this->pluginID,
                'version' => $this->version,
                'action' => 'plugin_update',
                'domain' => !empty(\network_site_url()) ? \network_site_url() : \get_home_url(),
                'locale' => \get_locale(),
            ];
        });
    }
}
