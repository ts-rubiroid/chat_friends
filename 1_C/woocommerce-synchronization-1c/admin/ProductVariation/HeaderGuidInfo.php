<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\ProductVariation;

class HeaderGuidInfo
{
    public function __construct()
    {
        \add_action('woocommerce_variation_header', [$this, 'id1cShow'], 10, 1);
    }

    /**
     * @param \WP_Post $variation
     */
    public function id1cShow($variation)
    {
        if (!$variation || !isset($variation->ID)) {
            return;
        }

        $guid = \get_post_meta($variation->ID, '_id_1c', true);

        echo '<br><small><b>'
            . \esc_html__('GUID', 'itgalaxy-woocommerce-1c')
            . '</b>: '
            . ($guid ? \esc_html($guid) : \esc_html__('no data', 'itgalaxy-woocommerce-1c'))
            . '</small>';
    }
}
