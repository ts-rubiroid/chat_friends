<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\AjaxActions;

use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;

class LastRequestResponseAjaxAction
{
    /**
     * Create new instance.
     *
     * @see https://developer.wordpress.org/reference/functions/add_action/
     * @see https://developer.wordpress.org/reference/hooks/wp_ajax__requestaction/
     *
     * @return void
     */
    public function __construct()
    {
        add_action('wp_ajax_ItglxWc1cLastRequestResponse', [$this, 'actionProcessing']);
    }

    /**
     * Execute the ajax action.
     *
     * @return void
     */
    public function actionProcessing()
    {
        if (!Helper::isUserCanWorkingWithExchange()) {
            exit;
        }

        $info = \get_option(Bootstrap::OPTION_INFO_KEY, []); ?>
        <p>
            <strong><?php esc_html_e('Last request from 1C', 'itgalaxy-woocommerce-1c'); ?>:</strong>
            <?php
            echo empty($info['last_request'])
                ? esc_html__('No requests have been made yet', 'itgalaxy-woocommerce-1c')
                : esc_html(
                    $info['last_request']['date']
                    . ' | '
                    . $info['last_request']['user']
                    . ' | '
                    . $info['last_request']['query']
                ); ?>
            <span class="itglx_fb_spinner-grow itglx_fb_spinner-grow-sm itglx_fb_d-none" role="status"></span>
        </p>
        <p class="itglx_fb_mb-0">
            <strong><?php esc_html_e('Last response for 1C', 'itgalaxy-woocommerce-1c'); ?>:</strong>
            <?php
            echo empty($info['last_response'])
                ? esc_html__('No response has been sent yet', 'itgalaxy-woocommerce-1c')
                : esc_html(
                    $info['last_response']['date']
                    . ' | '
                    . $info['last_response']['user']
                    . ' | '
                    . $info['last_response']['query']
                    . ' | '
                    . $info['last_response']['message']
                ); ?>
            <span class="itglx_fb_spinner-grow itglx_fb_spinner-grow-sm itglx_fb_d-none" role="status"></span>
        </p>
        <?php
        exit;
    }
}
