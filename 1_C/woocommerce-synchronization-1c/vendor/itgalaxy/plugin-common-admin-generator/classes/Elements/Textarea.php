<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;
use Itgalaxy\PluginCommon\AdminGenerator\Traits\ShapeWrappers;

class Textarea extends Component
{
    use ShapeWrappers;

    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $componentArgs = self::normalizeTextarea($componentArgs);
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' class="' . self::cssClass(self::getClasses($componentArgs, ['form-group' => true])) . '">';
        self::customContent($componentArgs, 'before');

        echo '<div class="' . self::cssClass(['form-input']) . '">';
        self::renderTitle($componentArgs);
        self::renderField($componentArgs);
        echo '</div>';

        self::customContent($componentArgs, 'after');
        echo '</div>';
    }

    /**
     * @param array $componentArgs
     *
     * @return void
     */
    private static function renderTitle($componentArgs)
    {
        self::customContent($componentArgs['title'], 'before');
        self::renderLabel($componentArgs['title'], ['textarea_attributes'], $componentArgs['textarea_attributes']['id']);
        self::customContent($componentArgs['title'], 'after');
    }

    /**
     * @param array $componentArgs
     *
     * @return void
     */
    private static function renderField($componentArgs)
    {
        $textarea = '<textarea ' . Html::arrayToAttributes($componentArgs['textarea_attributes']) . '>'
            . esc_html($componentArgs['text'])
            . '</textarea>';
        $textarea = self::shortcodesWrapper($componentArgs, $textarea, $componentArgs['textarea_attributes']['id']);
        $textarea = self::copyWrapper($componentArgs, $textarea, $componentArgs['textarea_attributes']['id']);

        echo self::pasteWrapper($componentArgs, $textarea, $componentArgs['textarea_attributes']['id']);

        self::renderDescription($componentArgs['description'], ['form-group-description']);
    }
}
