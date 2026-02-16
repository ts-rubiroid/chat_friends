<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

class Section
{
    public static function header($title)
    {
        ?>
        <div class="itglx_fb_bg-white itglx_fb_p-3 itglx_fb_mb-3 itglx_fb_border itglx_fb_border-info">
            <div class="itglx_fb_mb-2">
                <div class="itglx_fb_h4 itglx_fb_mb-1 itglx_fb_pb-2 itglx_fb_border-bottom itglx_fb_text-uppercase" aria-level="4" role="heading">
                    <?php echo esc_html($title); ?>
                </div>
            </div>
        <?php
    }

    public static function render($section)
    {
        self::header($section['title']);

        if (!empty($section['subtitle'])) {
            ?>
            <div class="itglx_fb_text-h5 itglx_fb_m-0">
                <?php echo esc_html($section['subtitle']); ?>
            </div>
            <hr>
            <?php
        }

        if (isset($section['tabs'])) {
            ?>
            <div data-ui-component="itglx-tabs">
                <ul class="nav itglx_fb_nav-pills" role="tablist">
                <?php
                foreach ($section['tabs'] as $tab) {
                    echo '<li class="nav-item" role="presentation"><button class="itglx_fb_nav-link'
                        . (reset($section['tabs']) === $tab ? ' active' : '')
                        . '" id="fb-tab_'
                        . esc_attr($tab['id'])
                        . '-tab" data-bs-toggle="pill" data-bs-target="#fb-tab_'
                        . esc_attr($tab['id'])
                        . '" role="tab" aria-controls="fb-tab_'
                        . esc_attr($tab['id'])
                        . '">'
                        . esc_html($tab['title'])
                        . '</button></li>';
                }

            echo '</ul>';
            echo '<div class="itglx_fb_tab-content">';

            foreach ($section['tabs'] as $tab) {
                echo '<div id="fb-tab_'
                    . esc_attr($tab['id'])
                    . '" class="fade itglx_fb_tab-pane'
                    . (reset($section['tabs']) === $tab ? ' show active' : '')
                    . '" role="tabpanel">';

                self::body($tab['fields']);

                echo '</div>';
            }

            echo '</div>';
            ?>
            </div>
            <?php
        } else {
            self::body($section['fields']);
        }

        self::footer();
    }

    public static function body($fields)
    {
        foreach ($fields as $name => $field) {
            if (isset($field['fieldsetStart'])) {
                ?>
                <fieldset class="itglx_fb_border itglx_fb_border-secondary itglx_fb_rounded itglx_fb_pb-2 itglx_fb_pt-1 itglx_fb_pl-3 itglx_fb_pr-3 itglx_fb_mb-2">
                    <?php if (!empty($field['legend'])) { ?>
                        <legend style="font-weight: 600;">
                            <?php echo esc_html($field['legend']); ?>
                        </legend>
                    <?php } ?>
                <?php
            }

            switch ($field['type']) {
                case 'checkbox':
                    FieldCheckbox::render($field, $name);
                    break;
                case 'select':
                    FieldSelect::render($field, $name);
                    break;
                case 'select2':
                    FieldSelect2::render($field, $name);
                    break;
                case 'text':
                case 'password':
                case 'number':
                case 'datetime-local':
                    FieldInput::render($field, $name);
                    break;
                case 'send_orders_status_mapping':
                    FieldSendOrdersStatusMapping::render($field, $name);
                    break;
                case 'textarea':
                    FieldTextArea::render($field, $name);
                    break;
                case 'content':
                    echo wp_kses_post($field['content']);
                    break;
                default:
                    // Nothing
                    break;
            }

            if (isset($field['fieldsetEnd'])) {
                echo '</fieldset>';
            } else {
                echo end($fields) !== $field ? '<hr>' : '';
            }
        }
    }

    public static function footer()
    {
        echo '</div>';
    }
}
