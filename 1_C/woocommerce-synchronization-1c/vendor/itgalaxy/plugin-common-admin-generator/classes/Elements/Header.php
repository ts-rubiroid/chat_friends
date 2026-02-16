<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;

class Header extends Component
{
    /**
     * @param array $componentArgs
     *
     * @return void
     */
    public static function render($componentArgs)
    {
        if (!empty($componentArgs['header'])) {
            $headerArgs = $componentArgs['header'];
        } else {
            $headerArgs = [];

            if (!empty($componentArgs['title'])) {
                $headerArgs['title'] = $componentArgs['title'];
            }

            if (!empty($componentArgs['description'])) {
                $headerArgs['description'] = $componentArgs['description'];
            }
        }

        $titleArgs = self::normalizeMeta($headerArgs, 'title');
        $descriptionArgs = self::normalizeMeta($headerArgs, 'description');

        if (empty($titleArgs) && empty($descriptionArgs)) {
            return;
        }

        $headerAttributes = self::normalizeAttributes($headerArgs);
        $headerAttributes['class'] = self::cssClass(self::getClasses($headerArgs, ['mb-3' => true]));
        $visibilityData = self::visibilityData($componentArgs);

        echo '<div ' . $visibilityData . ' ' . Html::arrayToAttributes($headerAttributes) . '>';
        self::renderHeaderTitle($titleArgs);
        self::renderHeaderDescription($descriptionArgs);
        echo '</div>';
    }

    /**
     * @param array $titleArgs
     *
     * @return void
     */
    private static function renderHeaderTitle($titleArgs)
    {
        if (empty($titleArgs)) {
            return;
        }

        $titleAttributes = self::normalizeAttributes($titleArgs);
        $titleAttributes['class'] = self::cssClass(self::getClasses($titleArgs, ['h2' => true, 'mb-1' => true]));
        $titleAttributes['aria-level'] = !empty($titleAttributes['aria-level']) ? $titleAttributes['aria-level'] : 2;
        $titleAttributes['role'] = 'heading';

        self::customContent($titleArgs, 'before');
        echo '<div ' . Html::arrayToAttributes($titleAttributes) /* escape ok */ . ' >';
        self::customContent($titleArgs, 'text');
        echo '</div>';
        self::customContent($titleArgs, 'after');
    }

    /**
     * @param array $descriptionArgs
     *
     * @return void
     */
    private static function renderHeaderDescription($descriptionArgs)
    {
        if (empty($descriptionArgs)) {
            return;
        }

        $descriptionAttributes = self::normalizeAttributes($descriptionArgs);
        $descriptionAttributes['class'] = self::cssClass(self::getClasses($descriptionArgs, ['text-h5' => true, 'm-0' => true]));

        self::customContent($descriptionArgs, 'before');
        echo '<div ' . Html::arrayToAttributes($descriptionAttributes) /* escape ok */ . '">';
        self::customContent($descriptionArgs, 'text');
        echo '</div>';
        self::customContent($descriptionArgs, 'after');
    }
}
