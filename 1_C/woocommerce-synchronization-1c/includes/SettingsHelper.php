<?php

namespace Itgalaxy\Wc\Exchange1c\Includes;

class SettingsHelper
{
    /**
     * @var array
     */
    public static $data;

    /**
     * Filling in data from an option.
     *
     * @return void
     */
    public static function init()
    {
        self::$data = \get_option(Bootstrap::OPTIONS_KEY, []);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public static function save($data = [])
    {
        \update_option(Bootstrap::OPTIONS_KEY, $data);

        // reload data
        self::init();
    }

    /**
     * @param int|string $key     Setting parameter name.
     * @param mixed      $default The value that will be returned if no such parameter is set. Default - null.
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (self::$data === null) {
            self::init();
        }

        return isset(self::$data[$key]) ? self::$data[$key] : $default;
    }

    /**
     * @param int|string $key Setting parameter name.
     *
     * @return bool
     */
    public static function isEmpty($key)
    {
        if (self::$data === null) {
            self::init();
        }

        return empty(self::$data[$key]);
    }
}
