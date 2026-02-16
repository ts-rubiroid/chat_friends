<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Table extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $attributes = self::normalizeAttributes($componentArgs);
        $attributes['class'] = self::cssClass(self::getClasses($componentArgs, ['table' => true]));
        $visibilityData = self::visibilityData($componentArgs); ?>
        <div <?php echo $visibilityData; ?>
            class="<?php echo self::cssClass(['table-responsive']); ?>">
            <?php Header::render($componentArgs); ?>
            <table <?php echo Html::arrayToAttributes($attributes); ?>>
                <?php self::renderChildes($componentArgs); ?>
            </table>
        </div>
        <?php
    }
}
