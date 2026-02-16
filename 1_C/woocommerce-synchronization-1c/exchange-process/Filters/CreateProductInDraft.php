<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class CreateProductInDraft
{
    private static $instance = false;

    private function __construct()
    {
        if (!SettingsHelper::isEmpty('product_create_new_in_status_draft')) {
            \add_filter('itglx_wc1c_insert_post_new_product_params', [$this, 'postParams']);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function postParams($params)
    {
        $params['post_status'] = 'draft';

        return $params;
    }
}
