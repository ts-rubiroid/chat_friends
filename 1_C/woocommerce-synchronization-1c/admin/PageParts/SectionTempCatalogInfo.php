<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\Wc\Exchange1c\Includes\Helper;

class SectionTempCatalogInfo
{
    public static function render()
    {
        Section::header(esc_html__('Temporary directory for exchange with 1C', 'itgalaxy-woocommerce-1c'));

        $message = Helper::existOrCreateDir(Helper::getTempPath()); ?>
        <p>
            <span class="<?php echo esc_attr($message['color']); ?>">
                <?php echo esc_html($message['text']); ?>
            </span>
        </p>
        <p class="description">
        <?php
        esc_html_e(
            'Files received from 1C during the exchange are loaded into this directory if it is not '
            . 'available for write and read, sharing will be impossible.',
            'itgalaxy-woocommerce-1c'
        ); ?>
        </p>
        <hr>
        <?php
        // check exists php-zip extension
        if (function_exists('zip_open')) {
            ?>
            <a href="<?php echo esc_url(admin_url()); ?>?itgxl-wc1c-temp-get-in-archive-only-xml=<?php echo esc_attr(uniqid()); ?>"
               class="itglx_fb_btn itglx_fb_btn-outline-info itglx_fb_text-decoration-none"
               target="_blank">
                <?php echo esc_html__('Download in zip archive (only XML files)', 'itgalaxy-woocommerce-1c'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url()); ?>?itgxl-wc1c-temp-get-in-archive=<?php echo esc_attr(uniqid()); ?>"
                class="itglx_fb_btn itglx_fb_btn-outline-info itglx_fb_text-decoration-none"
                target="_blank">
                <?php echo esc_html__('Download in zip archive (all)', 'itgalaxy-woocommerce-1c'); ?>
            </a>
        <?php
        } ?>
        <button class="itglx_fb_btn itglx_fb_btn-outline-danger"
            type="button"
            data-ui-component="itglx-wc1c-ajax-clear-temp"
            data-confirm-text="<?php esc_attr_e('Are you sure? The files in the temporary directory will be deleted.', 'itgalaxy-woocommerce-1c'); ?>">
            <span class="text">
                <?php echo esc_html__('Clear', 'itgalaxy-woocommerce-1c'); ?>
                <span data-ui-component="itglx-wc1c-temp-count-and-size-text"></span>
            </span>
            <span class="itglx_fb_spinner-grow itglx_fb_spinner-grow-sm" role="status"></span>
        </button>
        <?php

        Section::footer();
    }
}
