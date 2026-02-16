<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\Product;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class DataTabProduct
{
    public function __construct()
    {
        \add_filter('woocommerce_product_data_tabs', [$this, 'addTab'], 10, 1);
        \add_action('woocommerce_product_data_panels', [$this, 'tabContent']);
        \add_action('woocommerce_process_product_meta', [$this, 'tabContentSave'], 10, 1);
    }

    /**
     * @param array $tabs
     *
     * @return array
     */
    public function addTab($tabs)
    {
        $tabs['itgalaxy-woocommerce-1c-product-info'] = [
            'label' => \esc_html__('Exchange with 1C ', 'itgalaxy-woocommerce-1c'),
            'target' => 'itgalaxy-woocommerce-1c-product-info',
        ];

        return $tabs;
    }

    /**
     * @return void
     */
    public function tabContent()
    {
        // start tab content
        echo '<div id="itgalaxy-woocommerce-1c-product-info" class="panel woocommerce_options_panel">';

        \woocommerce_wp_text_input(
            [
                'id' => 'product_1c_guid',
                'value' => \esc_attr(\get_post_meta(\get_the_ID(), '_id_1c', true)),
                'label' => \esc_html__('GUID', 'itgalaxy-woocommerce-1c'),
            ]
        );

        echo '<hr>';

        // variable product info
        echo '<p class="show_if_variable"><strong>';
        \esc_html_e('Each variation has its own field with information about GUID.', 'itgalaxy-woocommerce-1c');
        echo '</strong></p>';

        $this->showPriceInfo();

        // end tab content
        echo '</div>';
    }

    /**
     * @param int $postID
     *
     * @return void
     */
    public function tabContentSave($postID)
    {
        if (!isset($_POST['product_1c_guid'])) {
            return;
        }

        \update_post_meta($postID, '_id_1c', \wp_unslash($_POST['product_1c_guid']));
    }

    /**
     * @return void
     */
    private function showPriceInfo()
    {
        // processing prices disabled
        if (!SettingsHelper::isEmpty('skip_product_prices')) {
            return;
        }

        echo '<p class="form-field hide_if_variable">';

        echo '<label>';
        \esc_html_e('Prices by types', 'itgalaxy-woocommerce-1c');
        echo '<br>';
        \esc_html_e('(guid | name | value)', 'itgalaxy-woocommerce-1c');
        echo '</label>';

        $allProductPrices = get_post_meta(get_the_ID(), '_all_prices', true);

        if (empty($allProductPrices)) {
            \esc_html_e('No data.', 'itgalaxy-woocommerce-1c');
        } else {
            $allPriceTypes = \get_option('all_prices_types', []);

            echo '<style>.description-block-1c-product-price {max-height: 100px; overflow-y: scroll; box-shadow: 0 0 0 transparent; border-radius: 4px; border: 1px solid #8c8f94; padding-left: 5px; background-color: #f0f0f1;}</style>';

            echo '<span class="description-block description-block-1c-product-price">';

            foreach ($allPriceTypes as $guid => $priceType) {
                $name = $priceType['name'];

                if (\mb_strlen($name) > 15) {
                    $name = \mb_substr($name, 0, 15) . '...';
                }

                echo \esc_html($guid)
                    . ' | <strong>'
                    . \esc_html($name)
                    . '</strong> | '
                    . (isset($allProductPrices[$guid]) ? $allProductPrices[$guid] : \esc_html__('no', 'itgalaxy-woocommerce-1c'))
                    . '<br/>';
            }

            echo '</span>';
        }

        echo '</p>';
    }
}
