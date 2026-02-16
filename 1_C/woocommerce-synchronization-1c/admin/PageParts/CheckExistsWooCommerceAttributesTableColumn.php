<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\PageParts;

use Itgalaxy\PluginCommon\AdminGenerator\Themes\OurStore;

class CheckExistsWooCommerceAttributesTableColumn
{
    public static function render()
    {
        global $wpdb;

        if (self::columnExists()) {
            return;
        }

        OurStore::callout(
            sprintf(
                '<strong>%1$s</strong>: %2$s',
                esc_html__('1C Data Exchange', 'itgalaxy-woocommerce-1c'),
                sprintf(
                    esc_html__(
                        'For some reason, there is no column `id_1c` in table `%s` that should have been added when '
                            . 'the plugin was activated. If there are properties in the unload, this will cause '
                            . 'a processing error. You can try reactivate plugin or add it yourself: '
                            . 'name - id_1c, type - varchar, length 191.',
                        'itgalaxy-woocommerce-1c'
                    ),
                    $wpdb->prefix . 'woocommerce_attribute_taxonomies'
                )
            ),
            'danger'
        );
    }

    /**
     * @return bool
     */
    private static function columnExists(): bool
    {
        global $wpdb;

        $dbName = DB_NAME;

        $columnExists = $wpdb->query(
            "SELECT * FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = '{$dbName}'
                  AND TABLE_NAME = '{$wpdb->prefix}woocommerce_attribute_taxonomies'
                  AND COLUMN_NAME = 'id_1c'"
        );

        return (bool) $columnExists;
    }
}
