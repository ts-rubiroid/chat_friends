<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\AjaxActions;

use Itgalaxy\Wc\Exchange1c\Includes\Helper;

class TempCountAndSizeAjaxAction
{
    /**
     * Create new instance.
     *
     * @see https://developer.wordpress.org/reference/functions/add_action/
     * @see https://developer.wordpress.org/reference/hooks/wp_ajax__requestaction/
     *
     * @return void
     */
    public function __construct()
    {
        add_action('wp_ajax_itglxWc1cTempCountAndSize', [$this, 'actionProcessing']);
    }

    /**
     * Execute the ajax action.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_send_json_success/
     *
     * @return void
     */
    public function actionProcessing()
    {
        if (!Helper::isUserCanWorkingWithExchange()) {
            exit;
        }

        wp_send_json_success(
            [
                'message' => $this->fileInfo(),
            ]
        );
    }

    /**
     * Method for obtaining information on files in the temporary directory.
     *
     * @see http://php.net/manual/en/recursiveiteratoriterator.construct.php
     *
     * @return string Information on the number of files and disk space in megabytes.
     */
    private function fileInfo()
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(Helper::getTempPath()),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        $size = 0;
        $countFiles = 0;

        foreach ($files as $name => $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();

            $size += filesize($filePath);
            ++$countFiles;
        }

        if ($countFiles === 0) {
            return esc_html__('(no files)', 'itgalaxy-woocommerce-1c');
        }

        return '<strong>'
            . sprintf(
                esc_html__(
                    '(files - %d, size - %s MB)',
                    'itgalaxy-woocommerce-1c'
                ),
                $countFiles,
                round($size / 1024 / 1024, 2) // show value in megabytes
            )
            . '</strong>';
    }
}
