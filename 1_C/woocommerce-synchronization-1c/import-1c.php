<?php

// Located in the root of the site

if (!file_exists(__DIR__ . '/wp-load.php')) {
    echo 'failure' . "\n" . 'no wp-load.php, WordPress can\'t be loaded';

    exit;
}

if (defined('ABSPATH') || empty($_GET['type'])) {
    exit;
}

const ITGLX_1C_EXCHANGE = true;

/**
 * @psalm-suppress MissingFile
 */
include_once __DIR__ . '/wp-load.php';

// if the plugin is loaded and executed, then the execution process will never reach this line, since there are 'exit' inside
echo 'failure' . "\n" . 'the plugin was not executed, it may not be activated';

exit;
