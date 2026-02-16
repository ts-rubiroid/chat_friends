<?php

namespace Itgalaxy\Wc\Exchange1c\Includes;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    private static $format = "[%datetime% | %request_id%] %channel%.%level_name%: %message% %context%\n";

    /**
     * @var MonologLogger
     */
    private static $log;

    /**
     * @var null|bool
     */
    private static $write;

    /**
     * Getting the path to the log directory.
     *
     * @return string Absolute path to the directory of log files of the current exchange.
     */
    public static function getLogPath()
    {
        $logsPath = Bootstrap::$pluginDir . 'files/site' . \get_current_blog_id() . '/logs';

        /**
         * Filters the value of the path to the directory where the log files are written.
         *
         * @since 1.117.0
         *
         * @param string $logsPath
         */
        return \apply_filters('itglx/wc1c/logs-path', $logsPath);
    }

    public static function startProcessingRequestLogProtocolEntry($ignoreWriteLastRequest = false)
    {
        if (!$ignoreWriteLastRequest && !isset($_GET['manual-1c-import'])) {
            $option = \get_option(Bootstrap::OPTION_INFO_KEY, []);

            $option['last_request'] = [
                'date' => \date_i18n('Y-m-d H:i:s'),
                'user' => isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : 'non user',
                'query' => $_SERVER['QUERY_STRING'],
            ];

            \update_option(Bootstrap::OPTION_INFO_KEY, $option);
        }

        if (!isset($_SESSION['exchange_id'])) {
            $_SESSION['exchange_id'] = uniqid();
        }

        $_SESSION['request_id'] = uniqid();

        self::log('[START REQUEST]', self::getStartEndRequestData());

        // write cookies from request if not manual exchange
        if (!isset($_GET['manual-1c-import'])) {
            self::log('[cookie list]', $_COOKIE);
        }
    }

    public static function endProcessingRequestLogProtocolEntry()
    {
        self::log('[END REQUEST]', self::getStartEndRequestData());
    }

    public static function saveLastResponseInfo($message)
    {
        if (isset($_GET['manual-1c-import'])) {
            return;
        }

        $option = \get_option(Bootstrap::OPTION_INFO_KEY, []);

        $option['last_response'] = [
            'date' => \date_i18n('Y-m-d H:i:s'),
            'user' => isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : 'non user',
            'query' => $_SERVER['QUERY_STRING'],
            'message' => $message,
        ];

        \update_option(Bootstrap::OPTION_INFO_KEY, $option);
    }

    /**
     * Adding a line to the log.
     *
     * @param string $message
     * @param array  $data
     * @param string $type
     *
     * @return void
     */
    public static function log($message, $data = [], $type = 'info')
    {
        // false - if log disabled or path not writable
        if (self::$write === false) {
            return;
        }

        // null - not yet defined, detection occurs on first try
        if (self::$write === null) {
            self::$write = !SettingsHelper::isEmpty('enable_logs_protocol') && is_writable(self::getLogPath());

            if (!self::$write) {
                return;
            }

            if (empty($_SESSION['logSynchronizeProcessFile'])) {
                // prepare and set log file path
                self::setLogFilePathToSession(
                    self::generateLogFilePath()
                );
            }
        }

        try {
            if (empty(self::$log)) {
                self::$log = new MonologLogger('wc1c');

                $handler = new StreamHandler($_SESSION['logSynchronizeProcessFile'], MonologLogger::INFO);
                $handler->setFormatter(new LineFormatter(self::$format));

                self::$log->pushHandler($handler);

                self::$log->pushProcessor(function ($entry) {
                    return self::addClientData($entry);
                });
            }

            self::$log->{$type}($message, (array) $data);
        } catch (\Exception $exception) {
            // Nothing
        }
    }

    public static function clearOldLogs()
    {
        $logsPath = self::getLogPath() . '/';

        $oldDaySynchronizationLogs = (int) SettingsHelper::get('log_days', 0);

        if ($oldDaySynchronizationLogs <= 1) {
            $oldDaySynchronizationLogs = 5;
        }

        // time in seconds - default 5 days
        $expireTime = $oldDaySynchronizationLogs * 24 * 60 * 60;

        if (is_dir($logsPath)) {
            $dirHandler = opendir($logsPath);

            if ($dirHandler) {
                while (($file = readdir($dirHandler)) !== false) {
                    $timeSec = time();
                    $filePath = $logsPath . $file;
                    $timeFile = filemtime($filePath);

                    $time = $timeSec - $timeFile;

                    if (is_file($filePath) && $time > $expireTime) {
                        unlink($filePath);
                    }
                }

                closedir($dirHandler);
            }
        }
    }

    private static function generateLogFilePath()
    {
        $type = !empty($_GET['type']) ? $_GET['type'] : 'empty';

        if (defined('DOING_CRON') && DOING_CRON) {
            $type = 'cron';
        }

        return self::getLogPath()
            . '/'
            . esc_attr($type)
            . '_'
            . date_i18n('Y.m.d_H')
            . '.log1c';
    }

    private static function setLogFilePathToSession($logFile)
    {
        $_SESSION['logSynchronizeProcessFile'] = $logFile;
    }

    private static function addClientData($record)
    {
        $record['request_id'] = '';

        if (isset($_SESSION['exchange_id'])) {
            $record['request_id'] = $_SESSION['exchange_id'] . '.' . $_SESSION['request_id'];
        }

        return $record;
    }

    private static function getStartEndRequestData()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $data = [
            'Query' => $_SERVER['QUERY_STRING'],
            'Usage, mb' => (string) round(memory_get_usage() / 1024 / 1024, 2),
            'Peak, mb' => (string) round(memory_get_peak_usage() / 1024 / 1024, 2),
            'Ip' => $ip,
            'Method' => $_SERVER['REQUEST_METHOD'],
            'User' => isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : 'non user',
            'Site' => \site_url(),
        ];

        return $data;
    }
}
