<?php

namespace Itgalaxy\Wc\Exchange1c\Includes\SchedulerActions;

class UnlinkFileSchedulerAction
{
    private static $instance = false;

    private function __construct()
    {
        \add_action('itglx/wc1c/unlink-file-schedule', [$this, 'action'], 10, 1);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $filePath Absolute path to the file.
     *
     * @return void
     */
    public function action($filePath)
    {
        $files = \glob("{$filePath}*");

        if (empty($files)) {
            return;
        }

        foreach ($files as $filename) {
            \unlink($filename);
        }
    }
}
