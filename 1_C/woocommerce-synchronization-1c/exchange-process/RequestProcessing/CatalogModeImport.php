<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product\ImagesProduct;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ZipHelper;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\ParserXml;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\ParserXml31;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\SuccessResponse;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RootProcessStarter;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class CatalogModeImport
{
    /**
     * @throws \Exception
     */
    public static function process()
    {
        $baseName = basename(RootProcessStarter::getCurrentExchangeFileAbsPath());

        if (!isset($_SESSION['IMPORT_1C'])) {
            $_SESSION['IMPORT_1C'] = [];
        }

        if (
            isset($_SESSION['IMPORT_1C']['zip_file'])
            && file_exists($_SESSION['IMPORT_1C']['zip_file'])
        ) {
            ZipHelper::extract($_SESSION['IMPORT_1C']['zip_file']);

            $archiveFileName = basename($_SESSION['IMPORT_1C']['zip_file']);

            unset($_SESSION['IMPORT_1C']['zip_file']);

            throw new ProgressException("[zip] extracted - {$archiveFileName}");
        }

        /**
         * Filters the sign of ignoring file processing.
         *
         * @since 1.80.2
         *
         * @param bool   $ignoreProcessing Default: false.
         * @param string $filename         File basename.
         */
        $ignoreProcessing = \apply_filters('itglx_wc1c_ignore_catalog_file_processing', false, $baseName);

        if ($ignoreProcessing) {
            Logger::log('ignore file processing by `itglx_wc1c_ignore_catalog_file_processing', [$baseName]);

            self::success();

            return;
        }

        // check requested parse file exists
        if (!file_exists(RootProcessStarter::getCurrentExchangeFileAbsPath())) {
            throw new ProtocolException(esc_html('File not exists! - ' . $baseName));
        }

        /**
         * Load required user working functions.
         *
         * @psalm-suppress MissingFile
         */
        include_once ABSPATH . 'wp-includes/pluggable.php';

        /*
         * It is necessary for the correct writing of the value in `post_author`
         * that is, so that actions are performed from an authorized user
         */
        self::setAuthUser();

        /**
         * Load required image working functions.
         *
         * @psalm-suppress MissingFile
         */
        include_once ABSPATH . 'wp-admin/includes/image.php';
        /** @psalm-suppress MissingFile */
        include_once ABSPATH . 'wp-admin/includes/file.php';
        /** @psalm-suppress MissingFile */
        include_once ABSPATH . 'wp-admin/includes/media.php';

        // product image progress check
        if (ImagesProduct::hasInProgress()) {
            ImagesProduct::continueProgress();
        }

        // get version scheme
        $reader = new \XMLReader();
        $reader->open(RootProcessStarter::getCurrentExchangeFileAbsPath());
        $reader->read();
        $_SESSION['xmlVersion'] = (float) $reader->getAttribute('ВерсияСхемы');

        Logger::log(
            'XML, schema version - ' . $reader->getAttribute('ВерсияСхемы')
            . ', generation date - ' . $reader->getAttribute('ДатаФормирования')
        );

        // resolve parser base version
        if ($_SESSION['xmlVersion'] < 3) {
            $parser = new ParserXml();
        } else {
            $parser = new ParserXml31();
        }

        $parser->parse($reader);

        // clear session
        unset($_SESSION['IMPORT_1C']);

        if (
            strpos($baseName, 'offers') !== false
            || strpos($baseName, 'rests') !== false // scheme 3.1
        ) {
            $_SESSION['IMPORT_1C_PROCESS'] = [];
        }

        self::success();
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    private static function success()
    {
        $baseName = basename(RootProcessStarter::getCurrentExchangeFileAbsPath());

        SuccessResponse::getInstance()->send("import file {$baseName} completed");

        if (!\has_action('itglx_wc1c_exchange_catalog_import_file_processing_completed')) {
            return;
        }

        Logger::log('(catalog) has_action `itglx_wc1c_exchange_catalog_import_file_processing_completed` - run');

        /**
         * Hook makes it possible to perform some of your actions when the file is processing processing.
         *
         * @since 1.84.9
         *
         * @param string $baseName The name of the file that has been processed
         */
        \do_action('itglx_wc1c_exchange_catalog_import_file_processing_completed', $baseName);
    }

    /**
     * @return void
     */
    private static function setAuthUser()
    {
        if (\is_user_logged_in()) {
            return;
        }

        $userID = (int) SettingsHelper::get('exchange_post_author', 0);

        if (!$userID) {
            $users = \get_users(['role' => 'administrator', 'fields' => 'ID']);

            if (empty($users)) {
                return;
            }

            $userID = (int) $users[0];
        }

        $user = \get_userdata($userID);

        if (!$user || \is_wp_error($user)) {
            return;
        }

        \wp_set_current_user($userID);
    }
}
