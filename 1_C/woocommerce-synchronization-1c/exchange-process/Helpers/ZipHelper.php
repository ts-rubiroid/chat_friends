<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class ZipHelper
{
    /**
     * @param string $filename
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function extract($filename)
    {
        $zip = self::start($filename);

        // extract progress with progress
        for ($i = $_SESSION['zipExtractedEntries']; $i < $zip->numFiles; ++$i) {
            $result = $zip->extractTo(dirname($filename), $zip->getNameIndex($i));

            if ($result !== true) {
                throw new ProtocolException('[zip] failed extract entry ' . $zip->getNameIndex($i) . ', to - ' . dirname($filename));
            }

            /*
             * We must write the current + 1, so that in case of interruption, at the next stage of the progress
             * start from the next file and not from the current one.
             */
            $_SESSION['zipExtractedEntries'] = $i + 1;

            // if the timeout is over and not all files are unpacked, then we will interrupt the process
            if (HeartBeat::limitIsExceeded() && $_SESSION['zipExtractedEntries'] < $zip->numFiles) {
                Logger::log('[zip] extracted files - ' . $_SESSION['zipExtractedEntries']);

                throw new ProgressException('[zip] extract progress...');
            }
        }

        self::end($zip, $filename);
    }

    /**
     * @return bool
     */
    public static function isUseZip()
    {
        if (function_exists('zip_open') && !SettingsHelper::isEmpty('use_file_zip')) {
            return true;
        }

        // log if enabled but no extension
        if (!function_exists('zip_open') && !SettingsHelper::isEmpty('use_file_zip')) {
            Logger::log('[zip] settings is enabled but no extension `php-zip`');
        }

        return false;
    }

    /**
     * @param string $filename
     *
     * @see https://www.php.net/manual/en/class.ziparchive.php
     *
     * @return \ZipArchive
     *
     * @throws \Exception
     */
    private static function start($filename)
    {
        Logger::log('[zip] extract start - ' . basename($filename));

        Logger::log(
            '[zip] archive info',
            [
                'size' => round((float) filesize($filename) / MB_IN_BYTES, 3) . ' MB',
                'disk_free_space' => function_exists('disk_free_space')
                    ? round((float) disk_free_space(dirname($filename)) / MB_IN_BYTES, 2) . ' MB'
                    : 'no info',
            ]
        );

        $zip = new \ZipArchive();

        $result = $zip->open($filename);

        if ($result !== true) {
            throw new ProtocolException('[zip] failed open archive ' . $filename . ', code - ' . $result);
        }

        // progress counter, the index in the archive starts from 0
        if (!isset($_SESSION['zipExtractedEntries'])) {
            $_SESSION['zipExtractedEntries'] = 0;
        }

        return $zip;
    }

    /**
     * @param \ZipArchive $zip
     * @param string      $filename
     * @param bool        $unlink   Default: true.
     *
     * @return void
     *
     * @throws \Exception
     */
    private static function end($zip, $filename, $unlink = true)
    {
        Logger::log('[zip] full count files - ' . $_SESSION['zipExtractedEntries']);

        unset($_SESSION['zipExtractedEntries']);

        if ($zip->close() !== true) {
            throw new ProtocolException('[zip] failed close archive ' . $filename);
        }

        if ($unlink) {
            unlink($filename);
        }

        Logger::log('[zip] extract end and unlink - ' . basename($filename));
    }
}
