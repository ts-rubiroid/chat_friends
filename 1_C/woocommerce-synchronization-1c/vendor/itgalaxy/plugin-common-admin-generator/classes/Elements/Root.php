<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class Root extends Component
{
    /**
     * @param array $componentArgs
     * @param bool  $return        Default: false.
     *
     * @return string
     */
    public static function render($componentArgs, $return = false)
    {
        self::beginBlock();

        $componentType = empty($componentArgs['type']) ? false : $componentArgs['type'];

        if ($componentType === 'root') {
            echo '<div class="' . self::cssClass(self::getClasses($componentArgs, ['root' => true])) . '">';
        }

        if ($componentType) {
            if ($componentType !== 'root') {
                self::renderByType($componentArgs);
            } else {
                self::renderChildes($componentArgs);
            }
        } else {
            foreach ($componentArgs as $child) {
                self::renderByType($child);
            }
        }

        if ($componentType === 'root') {
            echo '</div>';
        }

        return self::endBlock($return);
    }
}
