<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FieldCheckbox
{
    public static function render($field, $name)
    {
        Root::render(
            [
                'type' => 'input',
                'input_attributes' => [
                    'id' => esc_attr(Bootstrap::OPTIONS_KEY . '_' . $name),
                    'name' => esc_attr(Bootstrap::OPTIONS_KEY . '[' . $name . ']'),
                    'type' => 'checkbox',
                    'checked' => !SettingsHelper::isEmpty($name),
                    'value' => '1',
                ],
                'title' => ' ' . esc_html($field['title']),
                'description' => !empty($field['description']) ? ['text' => $field['description']] : [],
                'after' => !empty($field['content']) ? '<hr>' . wp_kses_post($field['content']) : '',
            ]
        );
    }
}
