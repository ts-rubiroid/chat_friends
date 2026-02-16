<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FieldInput
{
    public static function render($field, $name)
    {
        $default = $field['default'] ?? '';

        $inputAttributes = [
            'id' => esc_attr(Bootstrap::OPTIONS_KEY . '_' . $name),
            'type' => isset($field['type']) ? esc_attr($field['type']) : 'text',
            'value' => SettingsHelper::get($name) ? trim(esc_attr(SettingsHelper::get($name))) : $default,
        ];

        // a read-only field doesn't need a name so it doesn't get submitted in the form
        if (isset($field['readonly'])) {
            $inputAttributes['readonly'] = true;
        } else {
            $inputAttributes['name'] = esc_attr(Bootstrap::OPTIONS_KEY . '[' . $name . ']');
        }

        if (isset($field['step'])) {
            $inputAttributes['step'] = $field['step'];
        }

        Root::render(
            [
                'type' => 'input',
                'input_attributes' => $inputAttributes,
                'title' => esc_html($field['title']),
                'shape' => $field['shape'] ?? [],
                'description' => !empty($field['description']) ? ['text' => $field['description']] : [],
                'after' => !empty($field['content']) ? '<hr>' . wp_kses_post($field['content']) : '',
            ]
        );
    }
}
