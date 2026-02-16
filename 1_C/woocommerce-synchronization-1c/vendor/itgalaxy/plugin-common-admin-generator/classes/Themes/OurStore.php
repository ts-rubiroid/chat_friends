<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Themes;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;

class OurStore extends DefaultTheme
{
    /**
     * @param string $code
     * @param string $action
     *
     * @return void
     */
    public static function licenseBlock($code, $action)
    {
        if ($code) {
            $titleAfter = '<span class="' . Root::cssClass(['text-success']) . '">'
                . esc_html__('verified', 'itgalaxy-plugin-common-admin-generator')
                . '</span>';
        } else {
            $titleAfter = '<span class="' . Root::cssClass(['text-danger']) . '">'
                . esc_html__('please verify your license key', 'itgalaxy-plugin-common-admin-generator')
                . '</span>';
        }

        $licenseSection = [
            'header' => [
                'title' => esc_html__('License verification', 'itgalaxy-plugin-common-admin-generator')
                    . ' - '
                    . $titleAfter,
            ],
            'childes' => [
                [
                    'type' => 'input',
                    'input_attributes' => [
                        'value' => !empty($code) ? esc_attr($code) : '',
                        'name' => 'purchase-code',
                        'id' => 'purchase-code',
                        'aria-required' => 'true',
                        'required' => true,
                    ],
                    'title' => [
                        'text' => esc_html__('License key', 'itgalaxy-plugin-common-admin-generator'),
                    ],
                    'description' => [
                        'text' => '<a href="https://plugins.itgalaxy.company/liczenziya-podderzhka/" target="_blank">'
                            . esc_html__('Where Is My License Key?', 'itgalaxy-plugin-common-admin-generator')
                            . '</a>',
                    ],
                ],
                [
                    'type' => 'div',
                    'classes' => ['mt-3', 'pb-2'],
                    'childes' => [
                        [
                            'type' => 'button',
                            'classes' => ['btn-primary'],
                            'text' => esc_attr__('Verify', 'itgalaxy-plugin-common-admin-generator'),
                            'attributes' => [
                                'type' => 'submit',
                                'name' => 'verify',
                            ],
                        ],
                        [
                            'type' => 'button',
                            'classes' => ['btn-outline-primary'],
                            'text' => esc_attr__('Unverify', 'itgalaxy-plugin-common-admin-generator'),
                            'attributes' => [
                                'type' => 'submit',
                                'name' => 'unverify',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        echo '<form method="post" '
            . 'action="#" '
            . 'id="itglx-license-verify" '
            . 'data-ui-component="itglx-license-block" '
            . 'data-action="' . esc_attr($action) . '"'
            . '>';
        self::section($licenseSection);
        echo '</form>';
    }
}
