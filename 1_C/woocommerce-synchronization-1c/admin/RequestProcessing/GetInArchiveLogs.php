<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class GetInArchiveLogs
{
    public function __construct()
    {
        if (!isset($_GET['itgxl-wc1c-logs-get-in-archive'])) {
            return;
        }

        // check exists php-zip extension
        if (!function_exists('zip_open')) {
            return;
        }

        // https://developer.wordpress.org/reference/hooks/init/
        add_action('init', [$this, 'requestProcessing']);
    }

    public function requestProcessing()
    {
        if (!Helper::isUserCanWorkingWithExchange()) {
            exit;
        }

        $file = Bootstrap::$pluginDir . 'files/site' . \get_current_blog_id() . '/' . \uniqid() . '.zip';

        /*
         * We register a pending task to delete the file, so that in case of problems during preparation or download,
         * this file does not remain and is deleted.
         */
        if (function_exists('\\as_schedule_single_action')) {
            // after 5 minutes
            \as_schedule_single_action(time() + 5 * 60, 'itglx/wc1c/unlink-file-schedule', [$file]);
        }

        $this->createArchive(Logger::getLogPath(), $file);

        header('Content-Type: application/zip');
        header(
            'Content-Disposition: attachment; filename="'
            . 'logs_('
            . Bootstrap::PLUGIN_VERSION
            . ')_'
            . date('Y-m-d_H:i:s')
            . '.zip"'
        );
        header('Content-Length: ' . filesize($file));

        readfile($file);
        unlink($file);

        exit;
    }

    private function createArchive($path, $filename)
    {
        // create empty file
        file_put_contents($filename, '');

        $zip = new \ZipArchive();
        $zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $countFiles = 0;

        foreach ($files as $name => $file) {
            // get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($path) + 1);

            if (!$file->isDir()) {
                // add current file to archive
                $zip->addFile($filePath, 'logs/' . $relativePath);
                ++$countFiles;
            } elseif ($relativePath !== false) {
                $zip->addEmptyDir('logs/' . $relativePath);
            }
        }

        if ($countFiles === 0) {
            $zip->addEmptyDir('logs');
        }

        // zip archive will be created only after closing object
        $zip->close();
    }
}
