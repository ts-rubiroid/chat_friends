<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Elements\Root;
use Itgalaxy\Wc\Exchange1c\Includes\Bootstrap;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FieldSelect2
{
    public static function render($field, $name)
    {
        $currentValues = SettingsHelper::get($name, []);

        if (isset($field['options'][''])) {
            unset($field['options']['']);
        }

        $options = [];

        foreach ($field['options'] as $optionValue => $optionLabel) {
            if (in_array($optionValue, $currentValues)) {
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
                'type' => 'select2',
                'select_attributes' => [
                    'id' => esc_attr(Bootstrap::OPTIONS_KEY . '_' . $name),
                    'name' => esc_attr(Bootstrap::OPTIONS_KEY . '[' . $name . '][]'),
                    'multiple' => true,
                ],
                'options' => $options,
                'title' => esc_html($field['title']),
                'description' => !empty($field['description']) ? ['text' => $field['description']] : [],
                'after' => !empty($field['content']) ? '<hr>' . wp_kses_post($field['content']) : '',
            ]
        );
    }
}
