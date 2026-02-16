<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class TablePart extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $attributes = self::normalizeAttributes($componentArgs);

        if (!empty($componentArgs['classes'])) {
            $attributes['class'] = self::cssClass(self::getClasses($componentArgs));
        }

        echo '<' . esc_attr($componentArgs['type']) . ' ' . Html::arrayToAttributes($attributes) . '>';
        self::renderChildes($componentArgs);
        echo '</' . esc_attr($componentArgs['type']) . '>';
    }
}
