<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Themes;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Component;
use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\PluginCommon\AdminGenerator\Helpers\AssetsHelper;
use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Utils;

class DefaultTheme
{
    /**
     * @return void
     */
    public static function enqueueStyle()
    {
        \wp_enqueue_style('itgalaxy-admin-generator', AssetsHelper::getUrlAssetFile('app.css'), [], null);
    }

    /**
     * @return void
     */
    public static function enqueueScript()
    {
        \wp_enqueue_script('itgalaxy-admin-generator', AssetsHelper::getUrlAssetFile('app.js'), [], null, true);
    }

    /**
     * @return void
     */
    public static function rootWrapperStart()
    {
        echo '<div class="wrap"><div class="' . Component::cssClass(['root']) . '">';
    }

    /**
     * @param string $title
     * @param string $description
     * @param array  $buttons
     *
     * @return void
     */
    public static function pageHeader($title, $description, $buttons)
    {
        Root::render(
            [
                'type' => 'header-page',
                'header' => [
                    'title' => [
                        'text' => esc_html($title),
                        'classes' => ['h2' => false, 'h1'],
                    ],
                    'description' => esc_html($description),
                ],
                'childes' => $buttons,
            ]
        );
    }

    /**
     * @param array $arguments
     */
    public static function section($arguments)
    {
        $default = [
            'type' => 'div',
            'classes' => ['bg-white', 'p-3', 'mb-3', 'border', 'border-info'],
        ];

        Root::render(Utils::arrayMergeDeepArray([$default, $arguments]));
    }

    /**
     * @param string $content
     * @param string $type    Default: info.
     *
     * @return void
     */
    public static function callout($content, $type = 'info')
    {
        Root::render(
            [
                'type' => 'content',
                'content' => '<div class="' . Component::cssClass(['bg-white', 'p-2', 'bl-callout', 'bl-callout-' . $type]) . '">'
                . \wp_kses_post($content)
                . '</div>',
            ]
        );
    }

    /**
     * @param array $args
     *
     * @return void
     */
    public static function logsBlock($args)
    {
        self::section(
            [
                'header' => [
                    'title' => esc_html__('Logging', 'itgalaxy-plugin-common-admin-generator'),
                ],
                'childes' => [
                    [
                        'type' => 'input',
                        'classes' => ['mb-3'],
                        'input_attributes' => [
                            'type' => 'checkbox',
                            'value' => '1',
                            'id' => 'enabled_logging',
                            'checked' => !empty($args['enabled']),
                            'data-action' => isset($args['enableAction']) ? $args['enableAction'] : '',
                            'data-ui-component' => 'itglx-log-enable',
                        ],
                        'title' => esc_html__('Enable logging', 'itgalaxy-plugin-common-admin-generator'),
                    ],
                    [
                        'type' => 'button-link',
                        'classes' => ['btn-outline-primary'],
                        'text' => esc_html__('Download log', 'itgalaxy-plugin-common-admin-generator'),
                        'attributes' => [
                            'href' => esc_url(isset($args['downloadUrl']) ? $args['downloadUrl'] : ''),
                            'target' => '_blank',
                        ],
                    ],
                    [
                        'type' => 'button-link',
                        'classes' => ['btn-outline-primary'],
                        'text' => esc_html__('Clear log', 'itgalaxy-plugin-common-admin-generator'),
                        'attributes' => [
                            'data-ui-component' => 'itglx-log-clear',
                            'data-action' => isset($args['clearAction']) ? $args['clearAction'] : '',
                            'href' => '#',
                        ],
                    ],
                    [
                        'type' => 'content',
                        'classes' => ['mt-3', 'pt-2', 'border-top'],
                        'content' => esc_html(!empty($args['filePath']) ? $args['filePath'] : ''),
                    ],
                ],
            ]
        );
    }

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
                . esc_html__('please verify your purchase code', 'itgalaxy-plugin-common-admin-generator')
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
                        'text' => esc_html__('Purchase code', 'itgalaxy-plugin-common-admin-generator'),
                    ],
                    'description' => [
                        'text' => '<a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">'
                            . esc_html__('Where Is My Purchase Code?', 'itgalaxy-plugin-common-admin-generator')
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

    /**
     * @return void
     */
    public static function rootWrapperEnd()
    {
        echo '</div></div>';
    }
}
