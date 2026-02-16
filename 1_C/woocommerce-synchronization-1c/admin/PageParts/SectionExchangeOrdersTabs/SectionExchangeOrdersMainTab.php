<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrdersTabs;

use Itgalaxy\Wc\Exchange1c\Admin\PageParts\SectionExchangeOrders;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\RequestProcessing\SaleModeQuery;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class SectionExchangeOrdersMainTab
{
    public static function getSettings()
    {
        return [
            'title' => esc_html__('Main', 'itgalaxy-woocommerce-1c'),
            'id' => 'unload-orders',
            'fields' => [
                'send_orders' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Unload orders', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, when exchanging with 1C, the site gives all changed and new orders '
                        . 'since the last synchronization.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'content' => self::sendOrdersInfoContent(),
                ],
                'send_orders_response_encoding' => [
                    'type' => 'select',
                    'title' => esc_html__('Response encoding:', 'itgalaxy-woocommerce-1c'),
                    'options' => [
                        'utf-8' => esc_html__('UTF-8', 'itgalaxy-woocommerce-1c'),
                        'cp1251' => esc_html__('CP1251 (windows-1251)', 'itgalaxy-woocommerce-1c'),
                    ],
                    'description' => esc_html__(
                        'If you have a problem with receiving orders and in 1C you see an error like '
                        . '"Failed to read XML", try changing the encoding.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_use_scheme31' => [
                    'type' => 'checkbox',
                    'title' => esc_html__('Use scheme 3.1', 'itgalaxy-woocommerce-1c'),
                    'description' => esc_html__(
                        'If enabled, then the unloading of orders will be formed indicating version 3.1, a '
                        . 'number of mandatory details, as well as nesting of documents in containers.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_last_success_export' => [
                    'title' => esc_html__('Date / time of last request:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'datetime-local',
                    'description' => esc_html__(
                        'At the next request for loading orders, which will come from 1C, the plugin will '
                        . 'unload new / changed orders starting from this date / time.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetStart' => true,
                    'legend' => esc_html__('Conditions for export orders', 'itgalaxy-woocommerce-1c'),
                ],
                'send_orders_date_create_start' => [
                    'title' => esc_html__('Date / time of order creation no earlier:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'datetime-local',
                    'description' => esc_html__(
                        'Use this setting if you want to set a lower bound for the date of creation of the '
                        . 'order that can be unloaded. If the order is created earlier than this date, '
                        . 'it will never be unloaded.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_date_create_end' => [
                    'title' => esc_html__('Date / time of order creation no later:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'datetime-local',
                    'description' => esc_html__(
                        'Use this setting if you want to set a upper bound for the date of creation of the '
                        . 'order that can be unloaded. If the order is created later than this date, '
                        . 'it will never be unloaded.',
                        'itgalaxy-woocommerce-1c'
                    ),
                ],
                'send_orders_exclude_if_status' => [
                    'title' => esc_html__('Do not unload orders in selected statuses:', 'itgalaxy-woocommerce-1c'),
                    'type' => 'select2',
                    'options' => SectionExchangeOrders::getOrderStatusList(),
                    'description' => esc_html__(
                        'Use this setting if you want to exclude orders in some status from unloading.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    'fieldsetEnd' => true,
                ],
            ],
        ];
    }

    private static function sendOrdersInfoContent()
    {
        $orders = !SettingsHelper::isEmpty('send_orders') ? SaleModeQuery::getOrders(false) : [];

        if (count($orders)) {
            $orderEditList = [];

            foreach ($orders as $orderID) {
                $orderEditList[] = '<a href="'
                    . get_edit_post_link($orderID)
                    . '" target="_blank">'
                    . (int) $orderID
                    . '</a>';
            }

            $content = sprintf(
                '%1$s (<strong>%2$d</strong>): %3$s',
                esc_html__(
                    'At the next request, 1C will receive the following orders in response',
                    'itgalaxy-woocommerce-1c'
                ),
                count($orders),
                implode(', ', $orderEditList)
            );
        } else {
            $content = '<strong>'
                . esc_html__('There are no orders to be unloaded at the next request.', 'itgalaxy-woocommerce-1c')
                . '</strong>';
        }

        $content .= sprintf(
            '<br>%1$s: <a href="%2$s" target="_blank">%3$s</a> / <a href="%4$s" target="_blank">%5$s</a>',
            esc_html__(
                'You can see the content that will receive 1C in response to the following request',
                'itgalaxy-woocommerce-1c'
            ),
            esc_url(admin_url()) . '?manual-1c-import=true&type=sale&mode=query',
            esc_html__('open', 'itgalaxy-woocommerce-1c'),
            esc_url(admin_url()) . '?manual-1c-import=true&type=sale&mode=query&download=' . uniqid(),
            esc_html__('download', 'itgalaxy-woocommerce-1c')
        );

        return $content;
    }
}
