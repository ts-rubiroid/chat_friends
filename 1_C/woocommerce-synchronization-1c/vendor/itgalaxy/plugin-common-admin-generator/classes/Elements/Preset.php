<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Utils;

class Preset extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $presetArgs = self::$presets[$componentArgs['name']];

        if (!empty($componentArgs['arguments'])) {
            $presetArgs = Utils::arrayMergeDeepArray([$presetArgs, $componentArgs['arguments']]);
        }

        self::renderByType($presetArgs);
    }
}
