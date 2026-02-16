<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Accordion extends Component
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
        $accordionParent = uniqid('fb-accordion_');
        $buttonActiveClass = '';
        $bodyActiveClass = ' collapse show';

        echo '<div class="accordion ' . self::cssClass(['accordion']) . '" '
            . 'data-ui-component="itglx-accordion" '
            . 'id="' . esc_attr($accordionParent) . '">';

        foreach ($childes as $child) {
            $visibilityData = self::visibilityData($child);
            echo '<div ' . $visibilityData . ' data-itglx-accordion-item class="' . self::cssClass(['accordion-item']) . '">';
            self::itemHeading($child, $buttonActiveClass);
            self::itemBody($child, $bodyActiveClass, $accordionParent);
            echo '</div>';

            $buttonActiveClass = ' collapsed';
            $bodyActiveClass = ' collapse';
        }

        echo '</div>';
    }

    /**
     * @param array  $child
     * @param string $buttonActiveClass
     *
     * @return void
     */
    private static function itemHeading($child, $buttonActiveClass)
    {
        echo '<div role="heading" aria-level="4" class="' . self::cssClass(['accordion-header']) . '">';

        echo '<button class="' . self::cssClass(['accordion-button']) . $buttonActiveClass . '" '
            . 'type="button" data-bs-toggle="collapse" data-bs-target="#'
            . esc_attr($child['id']) . '" aria-expanded="'
            . (empty($buttonActiveClass) ? 'true' : 'false') . '" aria-controls="'
            . esc_attr($child['id']) . '">'
            . esc_html($child['title'])
            . '</button>';

        echo '</div>';
    }

    /**
     * @param array  $child
     * @param string $bodyActiveClass
     * @param string $accordionParent
     *
     * @return void
     */
    private static function itemBody($child, $bodyActiveClass, $accordionParent)
    {
        echo '<div id="' . esc_attr($child['id']) . '" class="'
            . self::cssClass(['accordion-collapse']) . $bodyActiveClass . '" '
            . 'data-bs-parent="#' . esc_attr($accordionParent) . '">'
            . '<div class="' . self::cssClass(['accordion-body']) . '">';

        self::renderByType($child);

        echo '</div></div>';
    }
}
