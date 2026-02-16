<?php

namespace Itgalaxy\PluginCommon;

class PluginRequest
{
    /**
     * @var int|string
     */
    private $pluginID;

    /**
     * @var string
     */
    private $pluginVersion;

    /**
     * @var string
     */
    private $option;

    /**
     * @param int|string $pluginID
     * @param string     $pluginVersion
     * @param string     $option
     */
    public function __construct($pluginID, $pluginVersion, $option)
    {
        $this->pluginID = $pluginID;
        $this->pluginVersion = $pluginVersion;
        $this->option = $option;
    }

    /**
     * @param string $action
     * @param string $code
     *
     * @return string[]
     */
    public function code(string $action, string $code): array
    {
        $code = trim(str_replace(' ', '', $code));

        $response = $this->call($action, $code);

        if ($response instanceof \WP_Error) {
            // fix network connection problems
            if ($response->get_error_code() === 'http_request_failed') {
                if ($action === 'code_activate') {
                    $messageContent = \esc_html__('Success verify.', 'itgalaxy-plugin-common');
                    \update_site_option($this->option, $code);
                } else {
                    $messageContent = \esc_html__('Success unverify.', 'itgalaxy-plugin-common');
                    \update_site_option($this->option, '');
                }

                return [
                    'message' => $messageContent,
                    'state' => 'successCheck',
                ];
            }

            return [
                'message' => '(Code - ' . $response->get_error_code() . ') ' . $response->get_error_message(),
                'state' => 'failedCheck',
            ];
        }

        if ($response->status === 'successCheck' && $action === 'code_activate') {
            \update_site_option($this->option, $code);
        } else {
            \update_site_option($this->option, '');
        }

        return [
            'message' => $response->message,
            'state' => $response->status,
        ];
    }

    /**
     * @param string $action
     * @param string $code
     *
     * @return mixed
     */
    public function call(string $action, string $code = '')
    {
        if (empty($code)) {
            $code = \get_site_option($this->option, '');
        }

        $response = \wp_remote_post(
            'https://envato.itgalaxy.company/envato/plugin-request',
            [
                'body' => [
                    'purchaseCode' => $code,
                    'itemID' => $this->pluginID,
                    'version' => $this->pluginVersion,
                    'action' => $action,
                    'domain' => !empty(\network_site_url()) ? \network_site_url() : \get_home_url(),
                    'locale' => \get_locale(),
                ],
                'sslverify' => false,
                'data_format' => 'body',
                'timeout' => 30,
            ]
        );

        if (!$response instanceof \WP_Error) {
            // hosting block
            if ((int) \wp_remote_retrieve_response_code($response) === 405) {
                return new \WP_Error('http_request_failed', 'Not Allowed');
            }

            $response = json_decode(\wp_remote_retrieve_body($response));

            if (isset($response->status) && $response->status === 'stop') {
                \update_site_option($this->option, '');
            }
        }

        return $response;
    }
}
