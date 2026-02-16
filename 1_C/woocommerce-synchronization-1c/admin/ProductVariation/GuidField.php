<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\ProductVariation;

class GuidField
{
    public function __construct()
    {
        \add_action('woocommerce_product_after_variable_attributes', [$this, 'field'], 10, 3);
        \add_action('woocommerce_save_product_variation', [$this, 'save'], 10, 1);
    }

    /**
     * @param int      $i
     * @param array    $variationData
     * @param \WP_Post $variation
     */
    public function field($i, $variationData, \WP_Post $variation)
    {
        echo '<div>';

        \woocommerce_wp_text_input(
            [
                'id' => 'variation_1c_guid[' . \esc_attr($variation->ID) . ']',
                'label' => \esc_html__('GUID (1C exchange)', 'itgalaxy-woocommerce-1c'),
                'value' => \esc_attr(\get_post_meta($variation->ID, '_id_1c', true)),
            ]
        );

        echo '</div>';
    }

    /**
     * @param int $variationId
     */
    public function save($variationId)
    {
        if (!isset($_POST['product_id']) || !isset($_POST['variation_1c_guid'])) {
            return;
        }

        if (!isset($_POST['variation_1c_guid'][$variationId])) {
            return;
        }

        \update_post_meta($variationId, '_id_1c', \wp_unslash($_POST['variation_1c_guid'][$variationId]));
    }
}
