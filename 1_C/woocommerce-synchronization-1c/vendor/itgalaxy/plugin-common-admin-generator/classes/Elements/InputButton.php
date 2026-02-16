<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class InputButton extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $componentArgs['input_attributes']['class'] = self::cssClass(self::getClasses($componentArgs, ['btn' => true]));

        echo '<input ' . self::visibilityData($componentArgs) . ' ' . Html::arrayToAttributes($componentArgs['input_attributes']) . '>';
    }
}
