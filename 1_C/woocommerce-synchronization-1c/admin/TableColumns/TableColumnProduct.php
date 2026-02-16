<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\TableColumns;

class TableColumnProduct
{
    public function __construct()
    {
        // https://developer.wordpress.org/reference/hooks/manage_post-post_type_posts_custom_column/
        add_action('manage_product_posts_custom_column', [$this, 'add1cToNameValue'], 11, 2);
    }

    public function add1cToNameValue($columnName, $postID)
    {
        if ($columnName === 'name') {
            $guid = get_post_meta($postID, '_id_1c', true);

            echo '<br><strong>'
                . esc_html__('GUID: ', 'itgalaxy-woocommerce-1c')
                . '</strong>'
                . esc_html(
                    $guid
                        ? $guid
                        : esc_html__('no data', 'itgalaxy-woocommerce-1c')
                );
        }
    }
}
