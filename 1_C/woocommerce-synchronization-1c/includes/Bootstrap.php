<?php

namespace Itgalaxy\Wc\Exchange1c\Includes;

use Itgalaxy\PluginCommon\DependencyPluginChecker;
use Itgalaxy\PluginCommon\MainHelperLoader;
use Itgalaxy\Wc\Exchange1c\Admin\AdminLoader;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RootProcessStarter;
use Itgalaxy\Wc\Exchange1c\Includes\Actions\DeclareHPOSCompatibility;
use Itgalaxy\Wc\Exchange1c\Includes\Actions\DeleteAttachment;
use Itgalaxy\Wc\Exchange1c\Includes\Actions\WcBeforeCalculateTotalsSetCartItemPrices;
use Itgalaxy\Wc\Exchange1c\Includes\Actions\WooCommerceAttributeDeleted;
use Itgalaxy\Wc\Exchange1c\Includes\Filters\Plugins\WooCommerceStoreExporter;
use Itgalaxy\Wc\Exchange1c\Includes\Filters\WcCartItemPriceShowSalePrice;
use Itgalaxy\Wc\Exchange1c\Includes\Filters\WcGetPriceHtmlShowPriceListDetailProductPage;
use Itgalaxy\Wc\Exchange1c\Includes\SchedulerActions\UnlinkFileSchedulerAction;

class Bootstrap
{
    const PLUGIN_ID = '24768513';
    const PLUGIN_VERSION = '1.125.0-149';

    const OPTIONS_KEY = 'wc-itgalaxy-1c-exchange-settings';
    const OPTION_INFO_KEY = 'wc-itgalaxy-1c-exchange-additional-info';
    const OPTION_UNITS_KEY = 'itglx_wc1c_nomenclature_units';
    const PURCHASE_CODE_OPTIONS_KEY = 'wc-itgalaxy-1c-exchange-purchase-code';
    const CRON = 'wc-itgalaxy-1c-exchange-cron';

    const DEPENDENCY_PLUGIN_LIST = ['woocommerce/woocommerce.php'];

    public static $plugin = '';

    /**
     * @var string Absolute path (with a trailing slash) to the plugin directory.
     */
    public static $pluginDir;

    /**
     * @var string URL to the plugin directory (with a trailing slash).
     */
    public static $pluginUrl;

    /**
     * @var MainHelperLoader
     */
    public static $common;

    /**
     * @var Bootstrap
     */
    private static $instance;

    /**
     * @param string $file
     */
    protected function __construct(string $file)
    {
        self::$plugin = $file;
        self::$pluginDir = \plugin_dir_path($file);
        self::$pluginUrl = \plugin_dir_url($file);
        self::$common = new MainHelperLoader($this, false);

        SettingsHelper::init();
        $this->pluginLifeCycleActionsRegister();

        if (!DependencyPluginChecker::isActivated(self::DEPENDENCY_PLUGIN_LIST)) {
            DependencyPluginChecker::showRequirementPluginsNotice(
                esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
                self::DEPENDENCY_PLUGIN_LIST
            );

            return;
        }

        $this->convertOldSettings();

        // bind cron actions
        Cron::getInstance();

        // additional hooks
        $this->bindAdditionalHooks();

        new Updater($this);

        // load admin functions
        add_action('plugins_loaded', static function () {
            new AdminLoader();
        });

        /**
         * Processing request from the accounting system.
         *
         * @see https://developer.wordpress.org/reference/hooks/init/
         */
        add_action('init', [$this, 'actionExchangeRequest'], PHP_INT_MAX);
    }

    /**
     * @param string $file
     *
     * @return Bootstrap
     */
    public static function getInstance(string $file): Bootstrap
    {
        if (!self::$instance) {
            self::$instance = new self($file);
        }

        return self::$instance;
    }

    /**
     * @return void
     */
    public function actionExchangeRequest(): void
    {
        // check is exchange request
        if (!Helper::isExchangeRequest()) {
            return;
        }

        // do not cache requests for `LiteSpeed Cache` - https://wordpress.org/plugins/litespeed-cache/
        \do_action('litespeed_disable_all', '1C exchange request');

        // exchange start
        RootProcessStarter::getInstance();
    }

    /**
     * @return void
     */
    public static function pluginActivation(): void
    {
        self::$common->requester->call('plugin_activate');

        DependencyPluginChecker::activateHelper(
            self::$plugin,
            self::DEPENDENCY_PLUGIN_LIST,
            esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c')
        );

        self::addWcAttributesTableColumn();
        self::copyRootEntryImportFile();

        $options = [
            self::OPTIONS_KEY => [
                'send_orders_last_success_export' => str_replace(' ', 'T', date_i18n('Y-m-d H:i')),
                'log_days' => 5,
            ],
            self::PURCHASE_CODE_OPTIONS_KEY => '',
            self::OPTION_INFO_KEY => [],
            'all_prices_types' => [],
            self::OPTION_UNITS_KEY => [],
            'itglx_wc1c_nomenclature_categories' => [],
            'currentAll1cGroup' => [],
            'all1cProducts' => [],
            'ITGALAXY_WC_1C_PLUGIN_VERSION' => self::PLUGIN_VERSION,
        ];

        foreach ($options as $name => $value) {
            if (\get_option($name) === false) {
                \add_option($name, $value, '', 'no');
            }
        }
    }

    /**
     * @return void
     */
    public static function pluginDeactivation(): void
    {
        self::$common->requester->call('plugin_deactivate');
        \wp_clear_scheduled_hook(self::CRON);
    }

    /**
     * @return void
     */
    public static function pluginUninstall(): void
    {
        self::$common->requester->call('plugin_uninstall');
    }

    /**
     * @return void
     *
     * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
     * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
     */
    private function pluginLifeCycleActionsRegister(): void
    {
        \register_activation_hook(self::$plugin, [self::class, 'pluginActivation']);
        \register_deactivation_hook(self::$plugin, [self::class, 'pluginDeactivation']);
    }

    /**
     * @return void
     */
    private function convertOldSettings(): void
    {
        if (
            get_option('ITGALAXY_WC_1C_PLUGIN_VERSION') === false
            || get_option('ITGALAXY_WC_1C_PLUGIN_VERSION') === self::PLUGIN_VERSION
        ) {
            return;
        }

        $settings = get_option(self::OPTIONS_KEY, []);

        if (empty($settings)) {
            \update_option('ITGALAXY_WC_1C_PLUGIN_VERSION', self::PLUGIN_VERSION);

            return;
        }

        // to 1.106.0
        if (!empty($settings['remove_missing_products'])) {
            $settings['remove_missing_full_unload_product_categories'] = 1;
            $settings['remove_missing_full_unload_products'] = 'completely';

            unset($settings['remove_missing_products']);
        }

        \update_option(self::OPTIONS_KEY, $settings);
        \update_option('ITGALAXY_WC_1C_PLUGIN_VERSION', self::PLUGIN_VERSION);
    }

    /**
     * @return void
     */
    private function bindAdditionalHooks(): void
    {
        // bind actions
        DeclareHPOSCompatibility::getInstance();
        DeleteAttachment::getInstance();
        WcBeforeCalculateTotalsSetCartItemPrices::getInstance();
        WooCommerceAttributeDeleted::getInstance();

        // bind scheduler actions
        UnlinkFileSchedulerAction::getInstance();

        // bind filters
        WcCartItemPriceShowSalePrice::getInstance();
        WcGetPriceHtmlShowPriceListDetailProductPage::getInstance();

        // other plugins
        WooCommerceStoreExporter::getInstance();
    }

    /**
     * @return void
     */
    private static function copyRootEntryImportFile(): void
    {
        if (!file_exists(self::$pluginDir . 'import-1c.php')) {
            return;
        }

        if (file_exists(ABSPATH . 'import-1c.php')) {
            return;
        }

        copy(self::$pluginDir . 'import-1c.php', ABSPATH . 'import-1c.php');
    }

    /**
     * @return void
     */
    private static function addWcAttributesTableColumn(): void
    {
        global $wpdb;

        $dbName = DB_NAME;

        // phpcs:disable
        $columnExists = $wpdb->query(
            "SELECT * FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = '{$dbName}'
                  AND TABLE_NAME = '{$wpdb->prefix}woocommerce_attribute_taxonomies'
                  AND COLUMN_NAME = 'id_1c'"
        );

        if (!$columnExists) {
            $wpdb->query(
                "ALTER TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies
                ADD id_1c varchar(191) NOT NULL"
            );
        }
        // phpcs:enable
    }
}
