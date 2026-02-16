<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Div extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $attributes = self::normalizeAttributes($componentArgs);
        $attributes['class'] = self::cssClass(self::getClasses($componentArgs));
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' ' . Html::arrayToAttributes($attributes) . '>';
        Header::render($componentArgs);
        self::renderChildes($componentArgs);
        echo '</div>';
    }
}
