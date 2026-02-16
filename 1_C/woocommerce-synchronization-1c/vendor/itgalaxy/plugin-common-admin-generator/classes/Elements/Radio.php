<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class Radio extends Component
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
        self::renderInputInsideLabel($componentArgs['title'], ['form-check-label', 'form-check-label-radio'], $componentArgs['input_attributes']);
        self::renderDescription($componentArgs['description'], ['form-group-description', 'form-check-description', 'form-check-description-radio']);
        echo '</div>';

        self::customContent($componentArgs, 'after');
        echo '</div>';
    }
}
