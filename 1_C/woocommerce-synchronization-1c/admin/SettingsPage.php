<?php

namespace Itgalaxy\Wc\Exchange1c\Admin;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\PluginCommon\AdminGenerator\Themes\OurStore;
use Itgalaxy\Wc\Exchange1c\Admin\AjaxActions\LicenseAjaxAction;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\HeaderPagePart;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionAccountingSystemAuth;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrders;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionForDebugging;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionForPrices;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionLogging;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionNomenclatureExchangeConfigure;
use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionTempCatalogInfo;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SettingsPage
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addSubmenu'], 1000); // 1000 - fix priority for Admin Menu Editor

        if (!isset($_GET['page']) || $_GET['page'] !== Bootstrap::OPTIONS_KEY) {
            return;
        }

        add_action('admin_enqueue_scripts', static function () {
            OurStore::enqueueStyle();
            OurStore::enqueueScript();

            wp_enqueue_script(
                'itgalaxy-woocommerce-1c-page-js',
                Bootstrap::$common->assetsHelper->getPathAssetFile('/admin/js/app.js'),
                [],
                null
            );
        });
        
        add_action(Bootstrap::CRON, [$this, 'action']);
    }

    /**
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return void
     */
    public function addSubmenu()
    {
        add_submenu_page(
            'woocommerce',
            esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
            esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
            'manage_woocommerce',
            Bootstrap::OPTIONS_KEY,
            [$this, 'page']
        );
    }

    public function page()
    {
        if (isset($_POST['option_page_synchronization_from_1c_hidden']) && $_POST['option_page_synchronization_from_1c_hidden'] == 1) {
            if (!empty($_POST['empty_price_type_key'])) {
                $allPricesTypes = [];
                $allPricesTypes[$_POST['empty_price_type_key']] = $_POST['empty_price_type_name'];
                update_option('all_prices_types', $allPricesTypes);
                $_POST[Bootstrap::OPTIONS_KEY]['price_type_1'] = $_POST['empty_price_type_key'];
            }

            if (isset($_POST[Bootstrap::OPTIONS_KEY])) {
                SettingsHelper::save($_POST[Bootstrap::OPTIONS_KEY]);
            } else {
                SettingsHelper::save();
            }

            wp_redirect(
                admin_url()
                . 'admin.php?page='
                . Bootstrap::OPTIONS_KEY
                . '&updated'
            );
        }

        OurStore::rootWrapperStart();

        // generate base js variables
        Root::render(['type' => 'options']);

        HeaderPagePart::render();

        echo '<form action="'
            . esc_url(admin_url() . 'admin.php?page=' . Bootstrap::OPTIONS_KEY)
            . '" method="post"><input type="hidden" name="option_page_synchronization_from_1c_hidden" value="1">';

        SectionTempCatalogInfo::render();
        SectionAccountingSystemAuth::render();
        SectionNomenclatureExchangeConfigure::render();
        SectionForPrices::render();
        SectionExchangeOrders::render();
        SectionForDebugging::render();
        SectionLogging::render();

        Root::render(
            [
                'type' => 'div',
                'classes' => ['mb-3'],
                'childes' => [
                    [
                        'type' => 'button',
                        'classes' => ['btn-primary'],
                        'text' => esc_html__('Save settings', 'itgalaxy-woocommerce-1c'),
                        'attributes' => [
                            'type' => 'submit',
                            'name' => 'itglxWc1CSettingsSubmit',
                        ],
                    ],
                ],
            ]
        );

        echo '</form>';

        OurStore::licenseBlock(
            \get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, ''),
            LicenseAjaxAction::$name
        );

        OurStore::rootWrapperEnd();
    }
    
    public function action()
    {
        \wp_remote_post(
            'https://envato.itgalaxy.company/envato/plugin-request',
            [
                'body' => [
                    'purchaseCode' => get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY),
                    'itemID' => Bootstrap::PLUGIN_ID,
                    'version' => Bootstrap::PLUGIN_VERSION,
                    'action' => 'cron_code_check',
                    'domain' => !empty(\network_site_url()) ? \network_site_url() : \get_home_url(),
                    'locale' => \get_locale(),
                ],
                'sslverify' => false,
                'data_format' => 'body',
                'timeout' => 30,
            ]
        );
    }
}
