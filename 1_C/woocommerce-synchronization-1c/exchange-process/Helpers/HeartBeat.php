<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class HeartBeat
{
    /**
     * @var array
     */
    private static $step = [];

    /**
     * @var int Unix timestamp.
     */
    private static $startTime;

    /**
     * @var int The number of seconds available to execute. For example: 20.
     */
    private static $maxTime;

    /**
     * @var int Value in bytes. 0 - without limit.
     */
    private static $memoryLimit;

    public static function start()
    {
        if (!isset($_SESSION['IMPORT_1C']['heartbeat'])) {
            $_SESSION['IMPORT_1C']['heartbeat'] = [];
        }

        self::$memoryLimit = self::getMemoryLimit();
        self::$startTime = time();

        $timeLimit = (int) SettingsHelper::get('time_limit', 20);

        self::$maxTime = $timeLimit > 0 ? $timeLimit : 20;
    }

    /**
     * @param string     $type
     * @param \XMLReader $reader
     *
     * @return bool
     */
    public static function next($type, \XMLReader $reader)
    {
        if (!isset($_SESSION['IMPORT_1C']['heartbeat'][$type])) {
            $_SESSION['IMPORT_1C']['heartbeat'][$type] = 0;
        }

        if (!isset(self::$step[$type])) {
            self::$step[$type] = 0;
        }

        if (self::$step[$type] < $_SESSION['IMPORT_1C']['heartbeat'][$type]) {
            for ($i = self::$step[$type]; $i < $_SESSION['IMPORT_1C']['heartbeat'][$type]; ++$i) {
                ++self::$step[$type];
                $reader->next();
            }

            $reader->read();
        }

        ++$_SESSION['IMPORT_1C']['heartbeat'][$type];
        ++self::$step[$type];

        if (self::limitIsExceeded()) {
            return false;
        }

        return true;
    }

    /**
     * Method allows you to check if the execution limit is exceeded or not yet.
     *
     * @return bool
     */
    public static function limitIsExceeded()
    {
        if (!self::hasAvailableMemory() || !self::hasAvailableTime()) {
            return true;
        }

        return false;
    }

    /**
     * Method allows you to check if there is available time to execution.
     *
     * @return bool
     */
    private static function hasAvailableTime()
    {
        if (time() - self::$startTime >= self::$maxTime) {
            return false;
        }

        return true;
    }

    /**
     * Method allows you to check if there is available memory.
     *
     * @return bool
     */
    private static function hasAvailableMemory()
    {
        // if the limit is empty, then we assume that memory is always available
        if (empty(self::$memoryLimit)) {
            return true;
        }

        /**
         * Check if there is at least another 10 megabytes in the available memory.         *
         * This value was determined experimentally and allows you to have a margin before overflow and error.
         *
         * 10485760 - 10 megabytes
         */
        if (memory_get_usage() + 10485760 >= self::$memoryLimit) {
            return false;
        }

        return true;
    }

    /**
     * The method allows you to get the memory limit in bytes.
     *
     * If 0 is returned, it means that the limit is not set.
     *
     * @return int
     *
     * @see https://www.php.net/manual/ini.core.php#ini.memory-limit
     */
    private static function getMemoryLimit()
    {
        $limitString = \ini_get('memory_limit');

        // limit disabled
        if ((string) $limitString === '-1') {
            return 0;
        }

        // the limit value is specified as a number, so we use it as the number of bytes
        if (is_numeric($limitString)) {
            return (int) $limitString;
        }

        $unit = strtolower(\mb_substr($limitString, -1));
        $bytes = (int) \mb_substr($limitString, 0, -1);

        switch ($unit) {
            case 'k':
                $bytes *= 1024; // kilobytes
                break;
            case 'm':
                $bytes *= 1048576; // megabytes
                break;
            case 'g':
                $bytes *= 1073741824; // gigabytes
                break;
            default:
                $bytes = 0;
                break;
        }

        return $bytes;
    }
}
