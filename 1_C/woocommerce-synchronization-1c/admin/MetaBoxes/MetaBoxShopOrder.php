<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\MetaBoxes;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class MetaBoxShopOrder
{
    public function __construct()
    {
        if (SettingsHelper::isEmpty('handle_get_order_status_change')) {
            return;
        }

        \add_action('add_meta_boxes', [$this, 'addMetaBox']);
    }

    /**
     * @return void
     */
    public function addMetaBox(): void
    {
        $screen = class_exists('\\Automattic\\WooCommerce\\Utilities\\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled()
            ? \wc_get_page_screen_id('shop-order')
            : 'shop_order';

        \add_meta_box(
            'id_1c',
            esc_html__('Exchange with 1C ', 'itgalaxy-woocommerce-1c'),
            [$this, 'metaBoxContent'],
            $screen,
            'side',
            'high'
        );
    }

    /**
     * @param \WC_Order|\WP_Post $postOrOrderObject
     *
     * @return void
     */
    public function metaBoxContent($postOrOrderObject): void
    {
        $order = ($postOrOrderObject instanceof \WP_Post) ? \wc_get_order($postOrOrderObject->ID) : $postOrOrderObject;
        $requisites = $order->get_meta('_itgxl_wc1c_order_requisites', true);

        if (empty($requisites)) {
            echo '<strong>'
                . esc_html__('no data', 'itgalaxy-woocommerce-1c')
                . '</strong>';
        } else {
            foreach ($requisites as $name => $value) {
                echo '<strong>'
                    . esc_html($name)
                    . ':</strong> '
                    . esc_html($value)
                    . '<br>';
            }
        }
    }
}
