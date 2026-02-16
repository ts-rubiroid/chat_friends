<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class SectionLogging
{
    public static function render()
    {
        Section::header(esc_html__('Exchange logging', 'itgalaxy-woocommerce-1c')); ?>
        <p class="description">
        <?php
        esc_html_e(
            'Logs of exchange with 1C are recorded in this directory. If it is not available for writing and '
            . 'reading, then logging will not work.',
            'itgalaxy-woocommerce-1c'
        ); ?>
        </p>
        <?php
        $message = Helper::existOrCreateDir(Logger::getLogPath());

        if (!$message['status']) { ?>
            <span class="<?php echo esc_attr($message['color']); ?>">
                <?php echo esc_html($message['text']); ?>
            </span>
            <?php
        } else {
            ?>
            <span class="<?php echo esc_attr($message['color']); ?>">
                <?php echo esc_html($message['text']); ?>
            </span>
            <hr>
            <?php
            FieldInput::render(
                [
                    'title' => esc_html__('Store for (days):', 'itgalaxy-woocommerce-1c'),
                    'type' => 'number',
                    'description' => esc_html__(
                        'Logs older will be deleted when exchanging.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'default' => 5,
                ],
                'log_days'
            ); ?>
            <hr>
            <?php
            FieldCheckbox::render(
                [
                    'title' => esc_html__('Enable logging', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, when exchanging from 1C, logs of the exchange protocol are '
                        . 'recorded.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'enable_logs_protocol'
            ); ?>
            <hr>
            <?php
            // check exists php-zip extension
            if (function_exists('zip_open')) {
                ?>
                <a href="<?php echo esc_url(admin_url()); ?>?itgxl-wc1c-logs-get-in-archive=<?php echo esc_attr(uniqid()); ?>"
                    class="itglx_fb_btn itglx_fb_btn-outline-info itglx_fb_text-decoration-none"
                    target="_blank">
                    <?php echo esc_html__('Download in zip archive', 'itgalaxy-woocommerce-1c'); ?>
                </a>
            <?php
            } ?>
            <button class="itglx_fb_btn itglx_fb_btn-outline-danger"
                type="button"
                data-ui-component="itglx-wc1c-ajax-clear-logs"
                data-confirm-text="<?php esc_attr_e('Are you sure? This will clear the recorded log.', 'itgalaxy-woocommerce-1c'); ?>">
                <span class="text">
                    <?php echo esc_html__('Clear logs', 'itgalaxy-woocommerce-1c'); ?>
                    <span data-ui-component="itglx-wc1c-logs-count-and-size-text"></span>
                </span>
                <span class="itglx_fb_spinner-grow itglx_fb_spinner-grow-sm" role="status"></span>
            </button>
            <hr>
            <div data-ui-component="itglx-wc1c-ajax-last-request-response-container">
                <span class="itglx_fb_spinner-grow itglx_fb_spinner-grow-sm" role="status"></span>
            </div>
            <?php
        }

        Section::footer();
    }
}
