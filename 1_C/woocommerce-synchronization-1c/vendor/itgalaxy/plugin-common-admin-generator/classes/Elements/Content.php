<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class Content extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $visibilityData = self::visibilityData($componentArgs);
        echo '<div ' . $visibilityData . ' class="' . self::cssClass(self::getClasses($componentArgs)) . '">';

        Header::render($componentArgs);
        self::customContent($componentArgs, 'script');
        self::customContent($componentArgs, 'style');
        self::customContent($componentArgs, 'content');

        echo '</div>';
    }
}
