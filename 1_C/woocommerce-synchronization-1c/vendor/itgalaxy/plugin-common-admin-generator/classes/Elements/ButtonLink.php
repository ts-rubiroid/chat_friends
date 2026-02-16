<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class ButtonLink extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $componentArgs['tag'] = 'a';

        // default `data-loading` is disabled
        if (isset($componentArgs['attributes'])) {
            $componentArgs['attributes'] = array_merge(
                [
                    'data-loading' => false,
                ],
                $componentArgs['attributes']
            );
        } else {
            $componentArgs['attributes'] = [
                'data-loading' => false,
            ];
        }

        Button::render($componentArgs);
    }
}
