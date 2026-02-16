<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FieldTextArea
{
    public static function render($field, $name)
    {
        Root::render(
            [
                'type' => 'textarea',
                'textarea_attributes' => [
                    'id' => esc_attr(Bootstrap::OPTIONS_KEY . '_' . $name),
                    'name' => esc_attr(Bootstrap::OPTIONS_KEY . '[' . $name . ']'),
                ],
                'title' => esc_html($field['title']),
                'text' => SettingsHelper::get($name) ? esc_attr(SettingsHelper::get($name)) : '',
                'description' => !empty($field['description']) ? ['text' => $field['description']] : [],
                'after' => !empty($field['content']) ? '<hr>' . wp_kses_post($field['content']) : '',
            ]
        );
    }
}
