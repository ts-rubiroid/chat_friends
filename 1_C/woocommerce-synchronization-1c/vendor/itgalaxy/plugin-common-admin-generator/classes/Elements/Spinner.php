<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Spinner extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $attributes = self::normalizeAttributes($componentArgs);
        $attributes['class'] = self::cssClass(self::getClasses($componentArgs, [
            'spinner' => true,
            'spinner-border' => true,
        ]));
        $attributes['role'] = 'status';
        $attributes['data-loading-class'] = self::cssClass(['loading']);

        echo '<div ' . Html::arrayToAttributes($attributes) . '>'
            . '<span class="' . self::cssClass(['visually-hidden']) . '">'
            . esc_attr__('Loading', 'itgalaxy-plugin-common-admin-generator')
            . '...</span>'
            . '</div>';
    }
}
