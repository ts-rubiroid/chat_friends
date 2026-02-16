<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class MaxImageSize
{
    /**
     * @var array Example ['width' => 100, 'height' => 100].
     */
    private static $maxSize = [];

    private static $instance = false;

    private function __construct()
    {
        /**
         * Filters the values of the maximum width and height of the image.
         *
         * @since 1.102.0
         *
         * @param array $maxImageSize Example ['width' => 100, 'height' => 100]
         */
        $maxImageSize = \apply_filters('itglx/wc1c/max-image-size', []);

        if (empty($maxImageSize) || !is_array($maxImageSize)) {
            return;
        }

        self::$maxSize = $maxImageSize;

        \add_action('itglx/wc1c/before-add-image-to-site', [$this, 'imageReduce']);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $imagePath
     *
     * @return void
     */
    public function imageReduce($imagePath)
    {
        if (empty(self::$maxSize['width']) || empty(self::$maxSize['height'])) {
            return;
        }

        $editor = \wp_get_image_editor($imagePath);

        if (!$editor || \is_wp_error($editor)) {
            unset($editor);

            return;
        }

        $size = $editor->get_size();

        if ($size['width'] > self::$maxSize['width'] || $size['height'] > self::$maxSize['height']) {
            Logger::log(
                '(image) reduce size - ' . basename($imagePath),
                [
                    'original' => $size,
                    'max' => self::$maxSize,
                ]
            );

            $editor->resize(self::$maxSize['width'], self::$maxSize['height']);
            $editor->save($imagePath);
        }

        unset($editor);
    }
}
