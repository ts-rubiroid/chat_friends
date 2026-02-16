<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class Checkbox extends Component
{
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

        echo '<div class="' . self::cssClass(['form-check']) . '">';
        self::renderInputInsideLabel($componentArgs['title'], ['form-check-label'], $componentArgs['input_attributes']);
        self::renderDescription($componentArgs['description'], ['form-group-description', 'form-check-description']);
        echo '</div>';

        self::customContent($componentArgs, 'after');
        echo '</div>';
    }
}
