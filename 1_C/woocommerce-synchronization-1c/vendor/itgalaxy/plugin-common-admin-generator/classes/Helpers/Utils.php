<?php

namespace Itgalaxy\PluginCommon\AdminGenerator\Helpers;

class Utils
{
    /**
     * @param array $arrays
     *
     * @see https://api.drupal.org/api/drupal/includes%21bootstrap.inc/function/drupal_array_merge_deep_array/7.x
     *
     * @return array
     */
    public static function arrayMergeDeepArray($arrays)
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_integer($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::arrayMergeDeepArray([$result[$key], $value]);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}
