<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Traits;

trait Normalize
{
    /**
     * @param array $data
     *
     * @return array
     */
    protected static function normalizeAttributes($data)
    {
        if (empty($data['attributes'])) {
            return [];
        }

        if (is_array($data['attributes'])) {
            return $data['attributes'];
        }

        return [];
    }

    /**
     * @param array      $data
     * @param int|string $key
     *
     * @return array
     */
    protected static function normalizeMeta($data, $key)
    {
        if (empty($data[$key])) {
            return [];
        }

        if (is_array($data[$key])) {
            return $data[$key];
        }

        return [
            'text' => $data[$key],
        ];
    }

    /**
     * @param array $tabs
     *
     * @return array
     */
    protected static function normalizeTabs($tabs)
    {
        foreach ($tabs as &$tab) {
            $tab['id'] = !empty($tab['id']) ? $tab['id'] : uniqid('fb-tab_');
        }

        return $tabs;
    }

    /**
     * @param array $field
     *
     * @return array
     */
    protected static function normalizeInput($field)
    {
        $inputAttributes = !empty($field['input_attributes']) ? $field['input_attributes'] : [];

        $field['input_attributes'] = array_merge([
            'type' => 'text',
            'id' => uniqid('fb_'),
            'class' => self::cssClass(['form-group-input', 'form-input-input']),
        ], $inputAttributes);

        if (!empty($field['input_attributes']['checked'])) {
            $field['input_attributes']['checked'] = 'checked';
        }

        $field['title'] = self::normalizeMeta($field, 'title');
        $field['description'] = self::normalizeMeta($field, 'description');

        return $field;
    }

    /**
     * @param array $field
     *
     * @return array
     */
    protected static function normalizeTextarea($field)
    {
        $textareaAttributes = !empty($field['textarea_attributes']) ? $field['textarea_attributes'] : [];

        $field['textarea_attributes'] = array_merge([
            'id' => uniqid('fb_'),
        ], $textareaAttributes);

        if (!empty($field['textarea_attributes']['checked'])) {
            $field['input_attributes']['checked'] = 'checked';
        }

        $field['title'] = self::normalizeMeta($field, 'title');
        $field['description'] = self::normalizeMeta($field, 'description');

        return $field;
    }

    /**
     * @param int|string   $optionValue
     * @param array|string $optionLabel
     *
     * @return array
     */
    protected static function normalizeSelectOption($optionValue, $optionLabel)
    {
        if (is_array($optionLabel)) {
            return $optionLabel;
        }

        return [
            'attributes' => [
                'value' => $optionValue,
            ],
            'label' => $optionLabel,
        ];
    }

    /**
     * @param array $field
     *
     * @return array
     */
    protected static function normalizeSelect($field)
    {
        $selectAttributes = !empty($field['select_attributes']) ? $field['select_attributes'] : [];

        $field['select_attributes'] = array_merge([
            'id' => uniqid('fb_'),
            'class' => self::cssClass(['form-group-input', 'form-input-input']),
        ], $selectAttributes);

        $field['title'] = self::normalizeMeta($field, 'title');
        $field['description'] = self::normalizeMeta($field, 'description');

        $options = [];

        foreach ($field['options'] as $optionValue => $optionLabel) {
            if (is_array($optionLabel) && key_exists('optgroup', $optionLabel)) {
                $optGroup = [
                    'optgroup' => $optionLabel['optgroup']['label'],
                    'options' => [],
                ];

                foreach ($optionLabel['optgroup']['options'] as $groupOptionValue => $groupOptionLabel) {
                    $optGroup['options'][] = self::normalizeSelectOption($groupOptionValue, $groupOptionLabel);
                }

                $options[] = $optGroup;

                continue;
            }

            $options[] = self::normalizeSelectOption($optionValue, $optionLabel);
        }

        $field['options'] = $options;

        return $field;
    }
}
