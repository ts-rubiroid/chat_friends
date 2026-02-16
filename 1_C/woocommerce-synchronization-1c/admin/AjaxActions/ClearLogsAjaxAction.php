<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\AjaxActions;

use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

/**
 * Handling ajax request to clear the logs directory.
 */
class ClearLogsAjaxAction
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
        add_action('wp_ajax_itglxWc1cClearLogs', [$this, 'actionProcessing']);
    }

    /**
     * Action callback. Clearing the logs directory.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_send_json_success/
     * @see https://developer.wordpress.org/reference/functions/wp_send_json_error/
     *
     * @return void
     */
    public function actionProcessing()
    {
        if (!Helper::isUserCanWorkingWithExchange()) {
            exit;
        }

        $logsPath = Logger::getLogPath();

        if (!file_exists($logsPath)) {
            wp_send_json_success(
                [
                    'message' => esc_html__('Successfully cleared', 'itgalaxy-woocommerce-1c'),
                ]
            );
        }

        if (!is_writable($logsPath)) {
            wp_send_json_error(
                [
                    'message' => esc_html__('Not available for write', 'itgalaxy-woocommerce-1c'),
                ]
            );
        }

        Helper::removeDir($logsPath);
        mkdir($logsPath, 0755, true);

        wp_send_json_success(
            [
                'message' => esc_html__('Successfully cleared', 'itgalaxy-woocommerce-1c'),
            ]
        );
    }
}
