<?php

namespace Itgalaxy\PluginCommon;

class MainHelperLoader
{
    /**
     * @var null|PluginLogger
     */
    public $logger;

    /**
     * @var PluginRequest
     */
    public $requester;

    /**
     * @var AssetsHelper
     */
    public $assetsHelper;

    /**
     * MainHelperLoader constructor.
     *
     * @param object $bootstrap
     * @param bool   $withLogger
     */
    public function __construct($bootstrap, $withLogger = true)
    {
        if ($withLogger) {
            $this->logger = new PluginLogger($bootstrap::$pluginLogFile, $bootstrap::PLUGIN_VERSION);
        }

        $this->assetsHelper = new AssetsHelper($bootstrap::$pluginDir, $bootstrap::$pluginUrl);

        $this->requester = new PluginRequest(
            $bootstrap::PLUGIN_ID,
            $bootstrap::PLUGIN_VERSION,
            $bootstrap::PURCHASE_CODE_OPTIONS_KEY
        );
    }
}
