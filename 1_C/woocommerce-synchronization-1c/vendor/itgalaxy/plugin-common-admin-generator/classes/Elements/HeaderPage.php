<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

class HeaderPage extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $visibilityData = self::visibilityData($componentArgs); ?>
        <div <?php echo $visibilityData; ?>
            class="<?php echo self::cssClass(
                self::getClasses($componentArgs, ['row', 'row-low-padding', 'align-items-center', 'mb-3', 'mt-3'])
            ); ?>">
            <div class="<?php echo self::cssClass(['col', 'mb-1']); ?>">
                <?php Header::render($componentArgs); ?>
            </div>
            <?php self::renderChildes($componentArgs); ?>
        </div>
        <?php
    }

    /**
     * @param array $componentArgs
     *
     * @return void
     */
    protected static function renderChildes($componentArgs)
    {
        $childes = self::getChildren($componentArgs);

        if (empty($childes)) {
            return;
        }

        foreach ($childes as $child) {
            echo '<div class="' . self::cssClass(['col-auto', 'mb-1']) . '">';
            self::renderByType($child);
            echo '</div>';
        }
    }
}
