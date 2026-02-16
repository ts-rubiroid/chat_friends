<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Elements;

use Itgalaxy\PluginCommon\AdminGenerator\Helpers\Html;
use Itgalaxy\PluginCommon\AdminGenerator\Traits\Normalize;

class Component
{
    use Normalize;

    protected static $cssPrefix = 'itglx_fb_';

    protected static $presets = [];

    public static function beginBlock()
    {
        ob_start();
        /** @psalm-suppress InvalidArgument */
        ob_implicit_flush(false);
    }

    /**
     * @param bool $return Default: false.
     *
     * @return string
     */
    public static function endBlock($return = false)
    {
        $content = ob_get_clean();

        if (!$return) {
            echo $content;
            // escape ok

            return '';
        }

        return $content;
    }

    public static function cssClass($classes)
    {
        $result = [];

        foreach ($classes as $class) {
            $class = trim($class);

            if (empty($class)) {
                continue;
            }

            $result[] = self::$cssPrefix . $class;
        }

        return \esc_attr(implode(' ', $result));
    }

    public static function visibilityData($componentArgs)
    {
        $visibility = empty($componentArgs['visibility']) ? false : wp_json_encode($componentArgs['visibility']);

        if (!$visibility) {
            return '';
        }

        $visibilityKey = hash('md5', $visibility);

        wp_add_inline_script(
            'itgalaxy-admin-generator',
            "window.itglx_fb.visibility['" . $visibilityKey . "'] = " . $visibility . ';',
            'before'
        );

        return 'data-itglx-visibility="' . $visibilityKey . '" ';
    }

    protected static function getChildren($componentArgs)
    {
        if (is_array($componentArgs) && array_key_exists('childes', $componentArgs)) {
            return $componentArgs['childes'];
        }

        return false;
    }

    protected static function getShapeClasses($field)
    {
        $result = [];

        if (empty($field['shape'])) {
            return $result;
        }

        if (in_array('sep', $field['shape'])) {
            $result[] = 'separated';
        }

        if (in_array('lg', $field['shape'])) {
            $result[] = 'lg';
        }

        return $result;
    }

    protected static function getClasses($componentArgs, $classes = [])
    {
        $shapeClasses = self::getShapeClasses($componentArgs);

        $result = !empty($componentArgs['classes'])
            ? array_merge($classes, $shapeClasses, $componentArgs['classes'])
            : array_merge($classes, $shapeClasses);

        return Html::classNames($result);
    }

    protected static function renderChildes($componentArgs)
    {
        $childes = self::getChildren($componentArgs);

        if (empty($childes)) {
            return;
        }

        foreach ($childes as $child) {
            self::renderByType($child);
        }
    }

    protected static function customContent($data, $key, $isExist = false)
    {
        if (!empty($data[$key])) {
            if ($isExist) {
                return true;
            }
            if ($key === 'style' || $key === 'script') {
                echo wp_kses($data[$key], ['style' => [], 'script' => []]);
            } else {
                // Todo wp_kses_post вырезает инпуты. Это сильно урезает возможности вывода кастомного контента.
                // Todo должны ли мы по-умолчанию экранировать вывод?
                echo $data[$key];
            }
        }

        return false;
    }

    protected static function renderLabel($title, $classes, $id)
    {
        if (!self::customContent($title, 'text', true)) {
            return;
        }

        echo '<label class="' . self::cssClass($classes) . '" for="' . esc_attr($id) . '">';
        self::customContent($title, 'text');
        echo '</label>';
    }

    protected static function renderInputInsideLabel($title, $classes, $inputArgs)
    {
        if (!self::customContent($title, 'text', true)) {
            return;
        }

        echo '<label class="' . self::cssClass($classes) . '">'
            . '<input ' . Html::arrayToAttributes($inputArgs) . '>';
        self::customContent($title, 'text');
        echo '</label>';
    }

    protected static function renderDescription($description, $classes)
    {
        if (
            !self::customContent($description, 'before', true)
            && !self::customContent($description, 'text', true)
            && !self::customContent($description, 'after', true)
        ) {
            return;
        }

        echo '<div class="' . self::cssClass($classes) . '">';
        self::customContent($description, 'before');
        self::customContent($description, 'text');
        self::customContent($description, 'after');
        echo '</div>';
    }

    protected static function renderByType($componentArgs)
    {
        switch ($componentArgs['type']) {
            case 'preset':
                Preset::render($componentArgs);
                break;
            case 'options':
                Options::render($componentArgs);
                break;
            case 'spinner':
                Spinner::render($componentArgs);
                break;
            case 'header-page':
                HeaderPage::render($componentArgs);
                break;
            case 'header':
                Header::render($componentArgs);
                break;
            case 'button':
                Button::render($componentArgs);
                break;
            case 'button-link':
                ButtonLink::render($componentArgs);
                break;
            case 'accordion':
                Accordion::render($componentArgs);
                break;
            case 'tabs':
                Tabs::render($componentArgs);
                break;
            case 'tabs-v':
                TabsV::render($componentArgs);
                break;
            case 'tab':
                Tab::render($componentArgs);
                break;
            case 'fieldset':
                Fieldset::render($componentArgs);
                break;
            case 'div':
                Div::render($componentArgs);
                break;
            case 'content':
                Content::render($componentArgs);
                break;
            case 'table':
                Table::render($componentArgs);
                break;
            case 'thead':
            case 'tbody':
            case 'tfoot':
            case 'tr':
            case 'th':
            case 'td':
                TablePart::render($componentArgs);
                break;
            case 'input':
                $field = self::normalizeInput($componentArgs);

                switch ($field['input_attributes']['type']) {
                    case 'button':
                    case 'reset':
                    case 'submit':
                        InputButton::render($componentArgs);
                        break;
                    case 'checkbox':
                        Checkbox::render($field);
                        break;
                    case 'radio':
                        Radio::render($field);
                        break;
                    case 'text':
                    default:
                        Input::render($field);
                        break;
                }

                break;
            case 'textarea':
                Textarea::render($componentArgs);
                break;
            case 'select':
                Select::render($componentArgs);
                break;
            case 'select2':
                $componentArgs['select_attributes']['data-ui-component'] = 'itglx-select2';

                Select::render($componentArgs);
                break;
            default:
                // Nothing
                break;
        }
    }
}
