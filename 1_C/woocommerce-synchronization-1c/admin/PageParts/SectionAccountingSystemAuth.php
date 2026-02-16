<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

class SectionAccountingSystemAuth
{
    public static function render()
    {
        $section = [
            'title' => esc_html__('Settings for authorization 1C', 'itgalaxy-woocommerce-1c'),
            'subtitle' => esc_html__(
                'Use these details when setting up an exchange node in 1C.',
                'itgalaxy-woocommerce-1c'
            ),
            'fields' => [
                'enable_exchange' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Enable exchange', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If disabled, no exchange will be possible.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'sync_script_address' => [
                    'type' => 'text',
                    'title' => esc_html__('Sync Script Address:', 'itgalaxy-woocommerce-1c'),
                    'default' => esc_url(get_bloginfo('url') . '/import-1c.php'),
                    'readonly' => true,
                    'shape' => [
                        'copy' => [
                            'title' => esc_attr__('Copy to clipboard', 'itgalaxy-woocommerce-1c'),
                            'message' => esc_attr__('Link copied to clipboard', 'itgalaxy-woocommerce-1c'),
                        ],
                    ],
                ],
                'exchange_auth_username' => [
                    'type' => 'text',
                    'title' => esc_html__('User:', 'itgalaxy-woocommerce-1c'),
                ],
                'exchange_auth_password' => [
                    'type' => 'password',
                    'title' => esc_html__('Password:', 'itgalaxy-woocommerce-1c'),
                ],
            ],
        ];

        Section::render($section);
    }
}
