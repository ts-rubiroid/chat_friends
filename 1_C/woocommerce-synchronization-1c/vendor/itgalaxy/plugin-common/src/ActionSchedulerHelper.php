<?php

namespace Itgalaxy\PluginCommon;

class ActionSchedulerHelper
{
    /**
     * @param string $hook
     *
     * @see https://actionscheduler.org/api/
     *
     * @return int
     */
    public static function getCountPendingActions($hook)
    {
        $list = \as_get_scheduled_actions(
            [
                'hook' => $hook,
                'status' => \ActionScheduler_Store::STATUS_PENDING,
                'per_page' => 1000,
            ],
            'ids'
        );

        return count($list);
    }

    /**
     * @param string $optionPrefix
     * @param string $hook
     * @param array  $data
     *
     * @see https://actionscheduler.org/api/
     *
     * @return void
     */
    public static function registerSendFormAction($optionPrefix, $hook, $data)
    {
        $optionName = $optionPrefix . md5(json_encode($data));

        // if action already exists
        if (self::hasExistsAction($hook, [$optionName])) {
            return;
        }

        // save data for sending
        \update_option($optionName, $data, false);

        /**
         * Filters the value `timestamp` to be used when registering a task.
         *
         * When a job should start.
         *
         * @psalm-suppress TooManyArguments
         *
         * @since 2.1.0
         *
         * @param int    $timestamp Unix timestamp.
         * @param string $hook
         */
        $timestamp = \apply_filters('itglx/wc/action-scheduler/form-action-timestamp', time() + 15, $hook);

        \as_schedule_single_action($timestamp, $hook, [$optionName]);
    }

    /**
     * @param string $optionName
     *
     * @return array
     */
    public static function getDataForSendFormAction($optionName)
    {
        $data = \get_option($optionName, []);

        // after getting the data, we must delete it from the database so as not to store personal data
        \delete_option($optionName);

        return $data;
    }

    /**
     * @param string $hook
     * @param array  $args
     *
     * @return bool|int
     */
    public static function hasExistsAction($hook, $args)
    {
        if (function_exists('as_has_scheduled_action')) {
            return \as_has_scheduled_action($hook, $args);
        }

        return \as_next_scheduled_action($hook, $args);
    }
}
