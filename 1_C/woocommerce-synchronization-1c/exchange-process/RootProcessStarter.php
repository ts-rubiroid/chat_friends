<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\DataDeletingOnFullExchange;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\CreateProductInDraft;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FindAttribute;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FindAttributeValueTermId;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FindProductCatId;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FindProductId;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FindProductTagId;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\FixedSeparatedIdCharacteristicInOfferXmlData;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\IgnoreCatalogFileProcessing;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\MaxImageSize;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\OfferIsRemoved;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\ProductIsRemoved;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters\SkipProductByXml;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\FailureResponse;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Responses\ProgressResponse;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class RootProcessStarter
{
    private static $instance = false;

    private static $exchangeFileAbsolutePath = '';

    private function __construct()
    {
        $this->bindHooks();
        HeartBeat::start();

        // check session is start
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        \register_shutdown_function([$this, 'shutdownPhp']);

        Logger::startProcessingRequestLogProtocolEntry();

        try {
            $this->sentHeaders();
            $this->preparePaths();
            $this->checkExistsXmlExtension();
            $this->checkEnableExchange();
            $this->checkAuth();
            $this->prepareExchangeFileStructure();

            /**
             * Filters the list of exchange request types and set of handlers.
             *
             * Based on the protocol, the request type is contained in the GET parameter `type`.
             *
             * @since 1.84.1
             *
             * @param array $typeRequestProcessors Array where key is the type of request and value is an array of handlers.
             *
             * @see https://dev.1c-bitrix.ru/api_help/sale/algorithms/data_2_site.php Info about `catalog` type.
             * @see https://dev.1c-bitrix.ru/api_help/sale/algorithms/realtime.php Info about `listen` type.
             * @see https://dev.1c-bitrix.ru/api_help/sale/algorithms/doc_from_site.php Info about `sale` type.
             */
            $typeRequestProcessors = apply_filters('itglx_wc1c_exchange_request_type_handlers', [
                'catalog' => [
                    \Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing\Catalog::class,
                ],
                'listen' => [
                    \Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing\Listen::class,
                ],
                'sale' => [
                    \Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing\Sale::class,
                ],
            ]);

            if (!empty($typeRequestProcessors[$_GET['type']]) && is_array($typeRequestProcessors[$_GET['type']])) {
                foreach ($typeRequestProcessors[$_GET['type']] as $processor) {
                    if (class_exists($processor) && method_exists($processor, 'process')) {
                        $processor::process();
                    }
                }
            } else {
                throw new ProtocolException('unknown, empty or no handlers for this type');
            }
        } catch (ProgressException $exception) {
            // manual import auto progress
            if (isset($_GET['manual-1c-import']) && Helper::isUserCanWorkingWithExchange()) {
                header('refresh:1');
            }

            ProgressResponse::getInstance()->send($exception->getMessage());
        } catch (ProtocolException $error) {
            DataDeletingOnFullExchange::clearCache();
            FailureResponse::getInstance()->send($error->getMessage());
        } catch (\Exception $error) {
            DataDeletingOnFullExchange::clearCache();
            FailureResponse::getInstance()->send(
                implode(
                    ', ',
                    [
                        'catch',
                        $error->getCode(),
                        $error->getMessage(),
                        $error->getFile() . ':' . $error->getLine(),
                    ]
                )
            );
        } catch (\Throwable $error) {
            DataDeletingOnFullExchange::clearCache();
            FailureResponse::getInstance()->send(
                implode(
                    ', ',
                    [
                        'catch',
                        $error->getCode(),
                        $error->getMessage(),
                        $error->getFile() . ':' . $error->getLine(),
                    ]
                ),
                $error
            );
        }

        Logger::endProcessingRequestLogProtocolEntry();

        // stop execution anyway
        exit;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getCurrentExchangeFileAbsPath()
    {
        if (empty($_GET['filename'])) {
            throw new ProtocolException('empty or missed required parameter - `filename`');
        }

        if (!self::$exchangeFileAbsolutePath) {
            $filename = trim(str_replace('\\', '/', trim($_GET['filename'])), '/');
            $filename = apply_filters('itglx_wc1c_exchange_filename_parameter', $filename);
            $filename = Helper::getTempPath() . '/' . $filename;

            self::$exchangeFileAbsolutePath = $filename;
        }

        return self::$exchangeFileAbsolutePath;
    }

    public function shutdownPhp()
    {
        $error = error_get_last();

        if (!isset($error['type']) || $error['type'] !== E_ERROR) {
            return;
        }

        DataDeletingOnFullExchange::clearCache();
        FailureResponse::getInstance()->send('shutdown, ' . $error['message'], $error);
    }

    /**
     * @return void
     */
    private function bindHooks(): void
    {
        CreateProductInDraft::getInstance();
        new FindAttribute();
        new FindAttributeValueTermId();
        FindProductCatId::getInstance();
        FindProductTagId::getInstance();
        FindProductId::getInstance();
        ProductIsRemoved::getInstance();
        OfferIsRemoved::getInstance();
        SkipProductByXml::getInstance();
        FixedSeparatedIdCharacteristicInOfferXmlData::getInstance();
        MaxImageSize::getInstance();
        IgnoreCatalogFileProcessing::getInstance();
    }

    private function sentHeaders()
    {
        // If headers has been sent
        if (headers_sent()) {
            return;
        }

        // If is a request for orders
        if (isset($_GET['mode']) && $_GET['mode'] === 'query') {
            return;
        }

        // If is a request for info
        if (isset($_GET['mode']) && $_GET['mode'] === 'info') {
            return;
        }

        header('Content-Type: text/plain; charset=utf-8');
    }

    private function preparePaths()
    {
        Helper::existOrCreateDir(Helper::getTempPath());
        Helper::existOrCreateDir(Logger::getLogPath());
    }

    private function checkExistsXmlExtension()
    {
        if (!class_exists('\\XMLReader')) {
            throw new ProtocolException('Please install/enable `php-xmlreader` extension for PHP');
        }

        if (!function_exists('\\simplexml_load_string')) {
            throw new ProtocolException('Please install/enable `php-xml` extension for PHP');
        }
    }

    private function checkEnableExchange()
    {
        // exchange enabled
        if (SettingsHelper::isEmpty('enable_exchange')) {
            throw new ProtocolException(
                esc_html__('Error! Setting `Enable exchange` is not enabled.', 'itgalaxy-woocommerce-1c')
            );
        }

        $value = get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY);

        if (empty($value)) {
            throw new ProtocolException(
                esc_html__('Please verify the license key on the plugin settings page.', 'itgalaxy-woocommerce-1c')
            );
        }
    }

    private function checkAuth()
    {
        if (Helper::isUserCanWorkingWithExchange()) {
            return;
        }

        if (
            empty($_SERVER['PHP_AUTH_USER'])
            && empty($_SERVER['PHP_AUTH_PW'])
        ) {
            $this->fixCgiAuth();
        }

        if (
            empty($_SERVER['PHP_AUTH_USER'])
            && empty($_SERVER['PHP_AUTH_PW'])
        ) {
            throw new ProtocolException(
                esc_html__(
                    'Error! Empty login or password! Most likely your PHP is operating in cgi(fcgi) mode and '
                        . 'processing of the authorization header is not configured.',
                    'itgalaxy-woocommerce-1c'
                )
            );
        }

        // wrong login or password
        if (
            trim(wp_unslash($_SERVER['PHP_AUTH_USER'])) !== trim(SettingsHelper::get('exchange_auth_username', 'empty'))
            || trim(wp_unslash($_SERVER['PHP_AUTH_PW'])) !== trim(SettingsHelper::get('exchange_auth_password', 'empty'))
        ) {
            throw new ProtocolException(esc_html__('Error! Wrong login or password!', 'itgalaxy-woocommerce-1c'));
        }
    }

    /**
     * Method fills in empty user and password variables.
     *
     * Sometimes, the environment does not automatically process the header of the basic authorization http, but only
     * transfers the content of the header to the variable, so the login / password variables remain empty, so we can
     * process the header ourselves.
     *
     * @see https://www.php.net/manual/ru/features.http-auth.php#106285
     *
     * @return void
     */
    private function fixCgiAuth()
    {
        $environmentVariables = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
        ];

        foreach ($environmentVariables as $environmentVariable) {
            if (empty($_SERVER[$environmentVariable])) {
                continue;
            }

            if (preg_match('/Basic\s+(.*)$/i', $_SERVER[$environmentVariable], $matches) === 0) {
                continue;
            }

            Logger::log('fixed empty login/password through `fixCgiAuth`');

            [$name, $password] = explode(':', base64_decode($matches[1]));
            $_SERVER['PHP_AUTH_USER'] = trim($name);
            $_SERVER['PHP_AUTH_PW'] = trim($password);
        }
    }

    private function prepareExchangeFileStructure()
    {
        if (empty($_GET['filename'])) {
            return;
        }

        /*
         * example - import_files/imagename.jpg
         * in this case, we need to create a subfolder `import_files` inside the temporary directory
         */

        $filename = trim(str_replace('\\', '/', trim($_GET['filename'])), '/');
        $filename = apply_filters('itglx_wc1c_exchange_filename_parameter', $filename);
        $filename = Helper::getTempPath() . '/' . $filename;

        if (!file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0775, true);
        }
    }
}
