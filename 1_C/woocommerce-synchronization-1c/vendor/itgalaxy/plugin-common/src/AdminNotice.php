<?php

namespace Itgalaxy\PluginCommon;

class AdminNotice
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $message;

    /**
     * Create new instance.
     *
     * @param string $message
     * @param string $title
     * @param string $type    Default is 'error'.
     *
     * @return void
     *
     * @see https://developer.wordpress.org/reference/hooks/admin_notices/
     */
    public function __construct($message, $title, $type = 'error')
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;

        \add_action('admin_notices', [$this, 'render']);
    }

    /**
     * Displays a notification.
     *
     * @return void
     */
    public function render()
    {
        echo sprintf(
            '<div class="notice notice-%1$s"><p><strong>%2$s</strong>: %3$s</p></div>',
            $this->type,
            $this->title,
            $this->message
        );
    }
}
