<?php

namespace Itgalaxy\Wc\Exchange1c\Includes\Actions;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;

class DeclareHPOSCompatibility
{
    private static $instance = false;

    private function __construct()
    {
        add_action('before_woocommerce_init', [$this, 'action']);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function action()
    {
        if (!class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
            return;
        }

        FeaturesUtil::declare_compatibility('custom_order_tables', Bootstrap::$plugin, true);
    }
}
