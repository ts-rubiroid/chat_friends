<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class Options extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $shortcodesIsNotDefined = '<a target="_blank" href="https://github.com/itgalaxy/plugin-common-admin-generator#shortcodes">'
            . esc_html__('Add shortcodes', 'itgalaxy-plugin-common-admin-generator')
            . '</a> '
            . esc_html__('to display the list', 'itgalaxy-plugin-common-admin-generator');

        $shortcodesGroupIsNotDefined = '<a target="_blank" href="https://github.com/itgalaxy/plugin-common-admin-generator#shortcodes">'
            . esc_html__('Shortcode groups %groups% were not found on access point %access_key%. There are groups available in it: %allowed_groups%.', 'itgalaxy-plugin-common-admin-generator')
            . ' <a target="_blank" href="https://github.com/itgalaxy/plugin-common-admin-generator#shortcodes">'
            . esc_html__('Documentation', 'itgalaxy-plugin-common-admin-generator')
            . '.</a>';

        $script = "
if (!window.itglx_fb) {
    window.itglx_fb = {
        classPrefix: '" . self::$cssPrefix . "',
        errorMessages: {
            shortcodesIsNotDefined: '" . $shortcodesIsNotDefined . ".',
            shortcodesGroupIsNotDefined: '" . $shortcodesGroupIsNotDefined . "',
        },
        errors: [],
        shortcodes: {},
        visibility: {}
    };
}
        ";

        if (!empty($componentArgs['shortcodes'])) {
            $shortcodes = [];

            if (array_key_exists('groups', $componentArgs['shortcodes'])) {
                $shortcodes[] = $componentArgs['shortcodes'];
            } else {
                $shortcodes = $componentArgs['shortcodes'];
            }

            foreach ($shortcodes as $shortcodeEntry) {
                $accessKey = empty($shortcodeEntry['access_key']) ? 'default' : esc_attr($shortcodeEntry['access_key']);
                $shortcodes = empty($shortcodeEntry['groups']) ? [] : $shortcodeEntry['groups'];
                $shortcodesJson = wp_json_encode($shortcodes);

                if ($shortcodesJson) {
                    $script .= "
if (typeof window.itglx_fb.shortcodes['" . $accessKey . "'] !== 'undefined') {
    window.itglx_fb.errors.push(
        new Error(
            '"
            . esc_html__('You are trying to overwrite', 'itgalaxy-plugin-common-admin-generator')
            . " window.itglx_fb.shortcodes.' + ['" . $accessKey . "'] + '. "
            . esc_html__('When registering shortcodes, specify a unique "access_key"', 'itgalaxy-plugin-common-admin-generator') . "'
        )
    );
} else {
    window.itglx_fb.shortcodes['" . $accessKey . "'] = " . $shortcodesJson . ';
}
                ';
                }
            }
        }

        wp_add_inline_script('itgalaxy-admin-generator', $script, 'before');

        if (!empty($componentArgs['presets'])) {
            self::$presets = $componentArgs['presets'];
        }
    }
}
