<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Helpers;

class AssetsHelper
{
    /**
     * @param string $assetFileName
     *
     * @return string
     */
    public static function getUrlAssetFile($assetFileName)
    {
        if (!defined('ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_DIR')) {
            return '';
        }

        if (!defined('ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_URL')) {
            return '';
        }

        $manifestFile = ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_DIR . '/resources/compiled/manifest.json';

        if (!file_exists($manifestFile)) {
            return '';
        }

        $manifestContent = file_get_contents($manifestFile);

        if (empty($manifestContent)) {
            return '';
        }

        $decodedManifest = json_decode($manifestContent, true);

        if (!is_array($decodedManifest) || !isset($decodedManifest[$assetFileName])) {
            return '';
        }

        return ITGLX_PLUGIN_COMMON_ADMIN_GENERATOR_URL . 'resources/compiled/' . $decodedManifest[$assetFileName];
    }
}
