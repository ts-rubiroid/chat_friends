<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Select extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $componentArgs = self::normalizeSelect($componentArgs);
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' class="' . self::cssClass(self::getClasses($componentArgs, ['form-group' => true])) . '">';
        self::customContent($componentArgs, 'before');

        echo '<div class="' . self::cssClass(['form-select-wrapper']) . '">';
        self::customContent($componentArgs['title'], 'before');

        self::renderLabel($componentArgs['title'], ['form-group-label'], $componentArgs['select_attributes']['id']);
        self::customContent($componentArgs['title'], 'after');

        echo '<select ' . Html::arrayToAttributes($componentArgs['select_attributes']) . '>';

        foreach ($componentArgs['options'] as $option) {
            if (key_exists('optgroup', $option)) {
                echo '<optgroup label="' . esc_attr($option['optgroup']) . '">';

                foreach ($option['options'] as $groupOption) {
                    echo '<option ' . Html::arrayToAttributes($groupOption['attributes']) . '>'
                        . esc_html($groupOption['label'])
                        . '</option>';
                }

                echo '</optgroup>';

                continue;
            }

            echo '<option ' . Html::arrayToAttributes($option['attributes']) . '>'
                . esc_html($option['label'])
                . '</option>';
        }

        echo '</select>';

        self::renderDescription($componentArgs['description'], ['form-group-description']);
        echo '</div>';

        self::customContent($componentArgs, 'after');
        echo '</div>';
    }
}
