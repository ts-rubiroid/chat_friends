<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\Other;

class AdminNoticeIfHasTrashedProductWithGuid
{
    public function __construct()
    {
        // https://developer.wordpress.org/reference/hooks/admin_notices/
        add_action('admin_notices', [$this, 'notice']);
    }

    public function notice()
    {
        global $pagenow;

        if ($pagenow !== 'edit.php' || empty($_GET['post_type']) || $_GET['post_type'] !== 'product') {
            return;
        }

        if (!$this->hasProducts()) {
            return;
        }

        echo sprintf(
            '<div class="notice notice-warning"><p><strong>%1$s</strong>: %2$s</p></div>',
            esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
            esc_html__(
                'Please note that you have products with GUID deleted to the trash, they will continue to be updated '
                . 'while in the trash.',
                'itgalaxy-woocommerce-1c'
            )
        );
    }

    private function hasProducts()
    {
        $trashProductWithGuid = get_posts(
            [
                'post_type' => 'product',
                'post_status' => 'trash',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => '_id_1c',
                        'value' => '',
                        'compare' => '!=',
                    ],
                ],
            ]
        );

        return !empty($trashProductWithGuid);
    }
}
