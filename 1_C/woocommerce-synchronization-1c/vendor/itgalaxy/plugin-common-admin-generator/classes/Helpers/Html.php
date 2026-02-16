<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Helpers;

class Html
{
    /**
     * @return string[]
     */
    public static function classNames()
    {
        $args = func_get_args();

        $data = array_reduce($args, function ($carry, $arg) {
            if (is_array($arg)) {
                return array_merge($carry, $arg);
            }

            $carry[] = $arg;

            return $carry;
        }, []);

        $classes = array_map(function ($key, $value) {
            $condition = $value;
            $return = $key;

            /** @psalm-suppress TypeDoesNotContainType */
            if (is_int($key)) {
                $condition = null;
                $return = $value;
            }

            /** @psalm-suppress TypeDoesNotContainType */
            $isArray = is_array($return);

            /** @psalm-suppress TypeDoesNotContainType */
            $isObject = is_object($return);
            $isStringableType = !$isArray && !$isObject;

            $isStringableObject = $isObject && method_exists($return, '__toString');

            if (!$isStringableType && !$isStringableObject) {
                return null;
            }

            if ($condition === null) {
                return $return;
            }

            return $condition ? $return : null;
        }, array_keys($data), array_values($data));

        return array_filter($classes);
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    public static function arrayToAttributes($attributes)
    {
        return array_reduce(
            array_keys($attributes),
            function ($carry, $attributeName) use ($attributes) {
                $escapedTag = htmlspecialchars($attributeName);

                if ($escapedTag === '') {
                    return $carry;
                }

                $attributeValue = $attributes[$attributeName];

                if ($attributeValue === 'empty') {
                    return $attributeName;
                }

                if (is_bool($attributeValue) && $attributeValue === false) {
                    return $carry;
                }

                if (!empty($carry)) {
                    $carry .= ' ';
                }

                if (is_array($attributeValue)) {
                    $attributeValue = implode(' ', $attributeValue);
                }

                if (is_bool($attributeValue) && $attributeValue === true) {
                    return $carry . sprintf('%s', $escapedTag);
                }

                return $carry . sprintf('%s="%s"', $escapedTag, esc_attr($attributeValue));
            },
            ''
        );
    }
}
