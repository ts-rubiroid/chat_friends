<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class Tab extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' data-itglx-tab class="' . self::cssClass(self::getClasses($componentArgs)) . '">';

        self::renderChildes($componentArgs);

        echo '</div>';
    }
}
