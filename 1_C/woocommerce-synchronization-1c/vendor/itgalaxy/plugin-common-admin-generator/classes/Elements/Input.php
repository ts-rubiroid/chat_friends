<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;
use Itgalaxy\PluginCommon\AdminGenerator\Traits\ShapeWrappers;

class Input extends Component
{
    use ShapeWrappers;

    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' class="' . self::cssClass(self::getClasses($componentArgs, ['form-group' => true])) . '">';
        self::customContent($componentArgs, 'before');

        echo '<div class="' . self::cssClass(['form-input']) . '">';
        self::customContent($componentArgs['title'], 'before');

        self::renderLabel($componentArgs['title'], ['form-group-label'], $componentArgs['input_attributes']['id']);
        self::customContent($componentArgs['title'], 'after');

        $input = '<input ' . Html::arrayToAttributes($componentArgs['input_attributes']) . '>';
        $input = self::shortcodesWrapper($componentArgs, $input, $componentArgs['input_attributes']['id']);
        $input = self::copyWrapper($componentArgs, $input, $componentArgs['input_attributes']['id']);
        echo self::pasteWrapper($componentArgs, $input, $componentArgs['input_attributes']['id']);

        self::renderDescription($componentArgs['description'], ['form-group-description']);
        echo '</div>';

        self::customContent($componentArgs, 'after');
        echo '</div>';
    }
}
