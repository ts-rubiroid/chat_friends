<?php

namespace Itgalaxy\PluginCommon;

class CronHelper
{
    /**
     * The method allow to get the number of registered `WP Cron` tasks by the name of the hook.
     *
     * @param string $name Hook name
     *
     * @return int
     */
    public static function getCountJobsByName($name)
    {
        $cronJobs = get_option('cron', []);

        if (empty($cronJobs)) {
            return 0;
        }

        $count = 0;

        foreach ($cronJobs as $time => $cron) {
            if (empty($cron[$name])) {
                continue;
            }

            $count += count($cron[$name]);
        }

        return $count;
    }
}
