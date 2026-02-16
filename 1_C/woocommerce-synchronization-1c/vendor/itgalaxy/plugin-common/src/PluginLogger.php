<?php

namespace Itgalaxy\PluginCommon;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class PluginLogger
{
    /**
     * @var null|Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $logFile;

    /**
     * @var string
     */
    private $pluginVersion;

    /**
     * @var null|string
     */
    private $logRequestID;

    /**
     * @param string $path
     * @param string $version
     */
    public function __construct($path, $version)
    {
        $this->logFile = $path;
        $this->pluginVersion = $version;
    }

    /**
     * @return void
     */
    public function prepare()
    {
        $this->createDir();
        $this->createSecurityFiles();
    }

    /**
     * @param string $name
     * @param string $message
     * @param array  $data
     * @param string $type
     *
     * @return void
     *
     * @throws \Exception
     */
    public function log($name, $message, $data = [], $type = 'info')
    {
        if (empty($this->logger)) {
            $this->logger = new Logger($name);

            $handler = new StreamHandler($this->logFile, Logger::INFO);
            $handler->setFormatter(
                new LineFormatter(
                    "[%datetime% | %request_id%] %channel%.%level_name%: %message% %context%\n"
                )
            );

            $this->logger->pushHandler($handler);

            $this->logger->pushProcessor(function (array $entry) {
                return $this->addClientData($entry);
            });
        }

        if (empty($this->logRequestID)) {
            $this->logRequestID = uniqid();
        }

        $this->logger->{$type}($message, $data);
    }

    /**
     * @return never
     */
    public function logsGet()
    {
        if (!file_exists($this->logFile)) {
            header('Content-Type: plain/text');
            header('Content-Disposition: attachment; filename="logs_(' . $this->pluginVersion . ')_' . date('Y-m-d_H:i:s') . '.log"');

            esc_html_e('Empty logs', 'itgalaxy-plugin-common');

            exit;
        }
        // check exists php-zip extension
        if (function_exists('zip_open')) {
            $file = dirname($this->logFile) . '/' . uniqid() . '.zip';

            // create empty file
            file_put_contents($file, '');

            $zip = new \ZipArchive();
            $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFile($this->logFile, 'plugin.log');

            // zip archive will be created only after closing object
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="logs_(' . $this->pluginVersion . ')_' . date('Y-m-d_H:i:s') . '.zip"');
            header('Content-Length: ' . filesize($file));

            readfile($file);
            unlink($file);

            exit;
        }

        header('Content-Type: plain/text');
        header('Content-Disposition: attachment; filename="logs_(' . $this->pluginVersion . ')_' . date('Y-m-d_H:i:s') . '.log"');
        header('Content-Length: ' . filesize($this->logFile));

        readfile($this->logFile);

        exit;
    }

    /**
     * @return void
     */
    public function logsClear()
    {
        if (!file_exists($this->logFile) || !is_writable($this->logFile)) {
            return;
        }

        unlink($this->logFile);
    }

    /**
     * @return void
     */
    public function ajaxLogsClear()
    {
        if (!file_exists($this->logFile)) {
            \wp_send_json_success(
                [
                    'message' => \esc_html__('There is no log file yet or it is already empty', 'itgalaxy-plugin-common'),
                ]
            );
        }

        if (!is_writable($this->logFile)) {
            \wp_send_json_error(
                [
                    'message' => \esc_html__('Log file is not available for writing', 'itgalaxy-plugin-common'),
                ]
            );
        }

        $this->logsClear();

        \wp_send_json_success(
            [
                'message' => \esc_html__('Log file has been cleared successfully', 'itgalaxy-plugin-common'),
            ]
        );
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function addClientData($record)
    {
        $record['request_id'] = $this->logRequestID;

        return $record;
    }

    /**
     * @return void
     */
    private function createDir()
    {
        if (file_exists(dirname($this->logFile))) {
            return;
        }

        mkdir(dirname($this->logFile), 0755, true);
    }

    /**
     * @return void
     */
    private function createSecurityFiles()
    {
        if (!file_exists(dirname($this->logFile)) || !is_writable(dirname($this->logFile))) {
            return;
        }

        if (!file_exists(dirname($this->logFile) . '/index.html')) {
            file_put_contents(dirname($this->logFile) . '/index.html', '');
        }

        if (!file_exists(dirname($this->logFile) . '/.htaccess')) {
            file_put_contents(dirname($this->logFile) . '/.htaccess', "deny from all\n");
        }
    }
}
