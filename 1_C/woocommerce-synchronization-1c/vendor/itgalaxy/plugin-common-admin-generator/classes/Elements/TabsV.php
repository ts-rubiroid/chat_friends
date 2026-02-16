<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class TabsV extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        $attributes = self::normalizeAttributes($componentArgs);
        $attributes['class'] = self::cssClass(self::getClasses($componentArgs, ['mb-3' => true]));
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' ' . Html::arrayToAttributes($attributes) . '>';
        Header::render($componentArgs);
        self::renderChildes($componentArgs);
        echo '</div>';
    }

    /**
     * @param array $componentArgs
     *
     * @return void
     */
    protected static function renderChildes($componentArgs)
    {
        $childes = array_filter(self::getChildren($componentArgs), function ($tab) {
            return $tab['type'] === 'tab';
        });

        if (empty($childes)) {
            return;
        }

        $childes = self::normalizeTabs($childes);

        $wrapperClasses = !empty($componentArgs['wrapperClasses']) ? $componentArgs['wrapperClasses'] : [];

        echo '<div data-ui-component="itglx-tabs" class="'
            . self::cssClass(self::getClasses(['classes' => $wrapperClasses], ['d-flex' => true, 'align-items-start' => true, 'mb-3' => true]))
            . '">';

        self::tabsNav($componentArgs, $childes);
        self::tabsContent($componentArgs, $childes);

        echo '</div>';
    }

    /**
     * @param array $componentArgs
     * @param array $childes
     *
     * @return void
     */
    private static function tabsNav($componentArgs, $childes)
    {
        $navClasses = !empty($componentArgs['navClasses']) ? $componentArgs['navClasses'] : [];

        echo '<div class="nav '
            . self::cssClass(self::getClasses(['classes' => $navClasses], ['flex-column', 'flex-shrink-0', 'nav-pills', 'nav-pills-vertical', 'me-2']))
            . '" role="tablist" aria-orientation="vertical">';

        $activeClass = ' active';

        foreach ($childes as $child) {
            echo '<button class="' . self::cssClass(['nav-link']) . $activeClass . '" id="'
                . esc_attr($child['id'])
                . '-tab" data-bs-toggle="pill"'
                . ' data-bs-target="#'
                . esc_attr($child['id'])
                . '" role="tab" aria-controls="'
                . esc_attr($child['id'])
                . '" aria-selected="' . (!empty($activeClass) ? 'true' : 'false') . '">'
                . esc_html($child['title'])
                . '</button>';

            $activeClass = '';
        }

        echo '</div>';
    }

    /**
     * @param array $componentArgs
     * @param array $childes
     *
     * @return void
     */
    private static function tabsContent($componentArgs, $childes)
    {
        $activeClass = ' show active';
        $bodyClasses = !empty($componentArgs['bodyClasses']) ? $componentArgs['bodyClasses'] : [];

        echo '<div class="' . self::cssClass(self::getClasses(['classes' => $bodyClasses], ['tab-content', 'flex-grow-1'])) . '">';

        foreach ($childes as $child) {
            echo '<div class="fade ' . self::cssClass(['tab-pane']) . $activeClass . '" id="'
                . esc_attr($child['id'])
                . '" role="tabpanel" aria-labelledby="'
                . esc_attr($child['id'])
                . '-tab">';

            self::renderByType($child);

            echo '</div>';

            $activeClass = '';
        }

        echo '</div>';
    }
}
