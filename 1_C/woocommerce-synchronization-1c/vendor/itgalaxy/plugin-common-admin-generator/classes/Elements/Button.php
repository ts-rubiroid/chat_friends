<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Button extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $btnAttributes = array_merge(
            [
                'data-loading-class' => self::cssClass(['loading']),
                'data-loading' => 'auto',
            ],
            $componentArgs['attributes']
        );
        $btnTag = empty($componentArgs['tag']) ? 'button' : $componentArgs['tag'];
        $visibilityData = self::visibilityData($componentArgs); ?>
        <<?php echo esc_attr($btnTag) . ' ' . $visibilityData; ?>
            class="<?php echo self::cssClass(self::getClasses($componentArgs, ['btn' => true])); ?>"
            <?php echo Html::arrayToAttributes($btnAttributes); ?>>
            <?php
            echo empty($componentArgs['text']) ? '' : wp_kses_post($componentArgs['text']);
        echo ' ';
        Spinner::render(empty($componentArgs['spinner']) ? [] : $componentArgs['spinner']); ?>
        </<?php echo esc_attr($btnTag); ?>>
        <?php
    }
}
