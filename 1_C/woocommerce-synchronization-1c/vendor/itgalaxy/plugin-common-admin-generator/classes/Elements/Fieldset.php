<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Fieldset extends Component
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
        $visibilityData = self::visibilityData($componentArgs); ?>
        <div <?php echo $visibilityData; ?>
            <?php echo Html::arrayToAttributes($attributes); ?>>
            <?php Header::render($componentArgs); ?>
            <fieldset>
                <?php if (self::customContent($componentArgs, 'legend', true)) { ?>
                    <legend><?php self::customContent($componentArgs, 'legend'); ?></legend>
                <?php } ?>
                <?php self::renderChildes($componentArgs); ?>
            </fieldset>
        </div>
        <?php
    }
}
