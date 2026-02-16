<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\AjaxActions;

use Itgalaxy\PluginCommon\AdminGenerator\Themes\OurStore;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;

class LicenseAjaxAction
{
    public static $name = 'itglx/wc1c/license';

    public function __construct()
    {
        add_action('wp_ajax_' . self::$name, [$this, 'action']);
    }

    public function action()
    {
        if (!Helper::isUserCanWorkingWithExchange()) {
            exit;
        }

        if (isset($_POST['code'])) {
            $response = Bootstrap::$common->requester->code(
                isset($_POST['type']) && $_POST['type'] === 'verify' ? 'code_activate' : 'code_deactivate',
                trim(wp_unslash($_POST['code']))
            );

            if ($response['state'] == 'successCheck') {
                OurStore::callout(esc_html($response['message']), 'success');
            } elseif ($response['message']) {
                OurStore::callout(esc_html($response['message']), 'danger');
            }
        }

        OurStore::licenseBlock(
            \get_site_option(Bootstrap::PURCHASE_CODE_OPTIONS_KEY, ''),
            self::$name
        );

        exit;
    }
}
