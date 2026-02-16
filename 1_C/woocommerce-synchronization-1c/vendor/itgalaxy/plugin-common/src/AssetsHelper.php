<?php

namespace Itgalaxy\PluginCommon;

class AssetsHelper
{
    /**
     * @var array|mixed
     */
    private $manifest = [];

    /**
     * @var string
     */
    private $pluginUrl;

    /**
     * @param string $pluginDir
     * @param string $pluginUrl
     */
    public function __construct($pluginDir, $pluginUrl)
    {
        $manifestFile = $pluginDir . 'resources/compiled/mix-manifest.json';

        if (!file_exists($manifestFile)) {
            return;
        }

        $manifestContent = file_get_contents($manifestFile);

        if (empty($manifestContent)) {
            return;
        }

        $this->manifest = json_decode($manifestContent, true);
        $this->pluginUrl = $pluginUrl;
    }

    /**
     * @param string $assetFile
     *
     * @return string
     */
    public function getPathAssetFile($assetFile)
    {
        if (!is_array($this->manifest) || !isset($this->manifest[$assetFile])) {
            return '';
        }

        return $this->pluginUrl . 'resources/compiled' . $this->manifest[$assetFile];
    }
}
