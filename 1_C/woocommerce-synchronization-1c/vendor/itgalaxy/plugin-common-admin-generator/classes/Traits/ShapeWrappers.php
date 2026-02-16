<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Traits;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Button;

trait ShapeWrappers
{
    /**
     * @param array  $field
     * @param string $input
     * @param string $id
     *
     * @return string
     */
    protected static function shortcodesWrapper($field, $input, $id)
    {
        if (!empty($field['shape']) && (in_array('shortcodes', $field['shape']) || array_key_exists('shortcodes', $field['shape']))) {
            $shortcodeTitle = !empty($field['shape']['shortcodes']['title'])
                ? $field['shape']['shortcodes']['title']
                : esc_html__('Insert shortcode', 'itgalaxy-plugin-common-admin-generator');

            $shortcodeGroups = !empty($field['shape']['shortcodes']['groups']) ? wp_json_encode($field['shape']['shortcodes']['groups']) : '';

            if ($shortcodeGroups === false) {
                $shortcodeGroups = '';
            }

            $shortcodeAccessKey = !empty($field['shape']['shortcodes']['access_key']) ? esc_attr($field['shape']['shortcodes']['access_key']) : 'default';

            self::beginBlock(); ?>
            <div class="<?php echo self::cssClass(['shortcodes-wrapper']); ?>">
                <?php
                echo $input;
            Button::render([
                'classes' => ['btn', 'btn-default', 'action-btn', 'shortcodes-btn'],
                'attributes' => [
                    'title' => esc_attr($shortcodeTitle),
                    'data-loading' => false,
                    'data-ui-component' => 'itglx-offcanvas',
                    'data-shortcodes-target' => '#' . esc_attr($id),
                    'data-shortcodes-groups' => esc_attr($shortcodeGroups),
                    'data-shortcodes-access-key' => esc_attr($shortcodeAccessKey),
                ],
            ]); ?>
            </div>
            <?php
            return self::endBlock(true);
        }

        return $input;
    }

    /**
     * @param array  $field
     * @param string $input
     * @param string $id
     *
     * @return string
     */
    protected static function copyWrapper($field, $input, $id)
    {
        if (!empty($field['shape']) && (in_array('copy', $field['shape']) || array_key_exists('copy', $field['shape']))) {
            $btnTitle = !empty($field['shape']['copy']['title'])
                ? $field['shape']['copy']['title']
                : esc_html__('Copy', 'itgalaxy-plugin-common-admin-generator');

            $btnMessage = !empty($field['shape']['copy']['message'])
                ? $field['shape']['copy']['message']
                : esc_html__('Copied to clipboard', 'itgalaxy-plugin-common-admin-generator');

            self::beginBlock(); ?>
            <div class="<?php echo self::cssClass(['row', 'row-low-padding', 'flex-nowrap', 'action-wrapper']); ?>">
                <div class="<?php echo self::cssClass(['col']); ?>">
                    <?php echo $input; ?>
                </div>
                <div class="<?php echo self::cssClass(['col-auto', 'd-flex', 'align-items-center']); ?>">
                    <?php
                    Button::render([
                        'classes' => ['btn', 'btn-default', 'action-btn', 'copy-btn'],
                        'attributes' => [
                            'title' => esc_attr($btnTitle),
                            'data-loading' => false,
                            'data-clipboard-target' => '#' . esc_attr($id),
                            'data-message' => esc_attr($btnMessage),
                            'data-no-value-error-message' => esc_attr__('Error: Value not set', 'itgalaxy-plugin-common-admin-generator'),
                        ],
                    ]); ?>
                </div>
            </div>
            <?php
            return self::endBlock(true);
        }

        return $input;
    }

    /**
     * @param array  $field
     * @param string $input
     * @param string $id
     *
     * @return string
     */
    protected static function pasteWrapper($field, $input, $id)
    {
        if (!empty($field['shape']) && (in_array('paste', $field['shape']) || array_key_exists('paste', $field['shape']))) {
            $btnTitle = !empty($field['shape']['paste']['title'])
                ? $field['shape']['paste']['title']
                : esc_html__('Paste', 'itgalaxy-plugin-common-admin-generator');

            self::beginBlock(); ?>
            <div class="<?php echo self::cssClass(['row', 'row-low-padding', 'flex-nowrap', 'action-wrapper']); ?>">
                <div class="<?php echo self::cssClass(['col']); ?>">
                    <?php echo $input; ?>
                </div>
                <div class="<?php echo self::cssClass(['col-auto', 'd-flex', 'align-items-center']); ?>">
                    <?php
                    Button::render([
                        'classes' => ['btn', 'btn-default', 'action-btn', 'paste-btn'],
                        'attributes' => [
                            'title' => esc_attr($btnTitle),
                            'data-loading' => false,
                            'data-paste-target' => '#' . esc_attr($id),
                            'data-no-value-error-message' => esc_attr__('Error: buffer is empty', 'itgalaxy-plugin-common-admin-generator'),
                        ],
                    ]); ?>
                </div>
            </div>
            <?php
            return self::endBlock(true);
        }

        return $input;
    }
}
