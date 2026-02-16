<?php

namespace Itgalaxy\PluginCommon;

class CacheHelper
{
    /**
     * The method allows you to check if caching is applied on the site.
     *
     * @return bool
     */
    public static function siteUsesCache()
    {
        if (self::wpCacheConstantEnabled()) {
            return true;
        }

        return self::cachePluginEnabled();
    }

    /**
     * @return bool
     */
    private static function wpCacheConstantEnabled()
    {
        /** @psalm-suppress RedundantCondition */
        if (defined('WP_CACHE') && (bool) WP_CACHE) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private static function cachePluginEnabled()
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

        if (
            \is_plugin_active('wp-fastest-cache/wpFastestCache.php')
            || \is_plugin_active('sg-cachepress/sg-cachepress.php')
            || \is_plugin_active('litespeed-cache/litespeed-cache.php')
        ) {
            return true;
        }

        return false;
    }
}
