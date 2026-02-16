<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FieldSelect
{
    public static function render($field, $name)
    {
        $options = [];

        foreach ($field['options'] as $optionValue => $optionLabel) {
            if (SettingsHelper::get($name) == $optionValue) {
                $options[$optionValue] = [
                    'attributes' => [
                        'value' => $optionValue,
                        'selected' => true,
                    ],
                    'label' => $optionLabel,
                ];

                continue;
            }

            $options[$optionValue] = $optionLabel;
        }

        Root::render(
            [
                'type' => 'select',
                'select_attributes' => [
                    'id' => esc_attr(Bootstrap::OPTIONS_KEY . '_' . $name),
                    'name' => esc_attr(Bootstrap::OPTIONS_KEY . '[' . $name . ']'),
                ],
                'options' => $options,
                'title' => esc_html($field['title']),
                'description' => !empty($field['description']) ? ['text' => $field['description']] : [],
                'after' => !empty($field['content']) ? '<hr>' . wp_kses_post($field['content']) : '',
            ]
        );
    }
}
