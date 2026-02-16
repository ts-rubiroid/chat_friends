<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\TableColumns;

class TableColumnProductAttribute
{
    public function __construct()
    {
        add_action('woocommerce_init', [$this, 'init']);
    }

    public function init()
    {
        if (!is_admin() || !function_exists('wc_get_attribute_taxonomies')) {
            return;
        }

        $taxonomies = \wc_get_attribute_taxonomies();

        foreach ($taxonomies as $taxonomy) {
            // https://developer.wordpress.org/reference/hooks/manage_screen-id_columns/
            add_filter('manage_edit-pa_' . $taxonomy->attribute_name . '_columns', [$this, 'add1cValueColumn'], 10, 3);

            // https://developer.wordpress.org/reference/hooks/manage_this-screen-taxonomy_custom_column/
            add_filter('manage_pa_' . $taxonomy->attribute_name . '_custom_column', [$this, 'add1cValue'], 10, 3);
        }
    }

    public function add1cValueColumn($columns)
    {
        $columns['wc1c'] = esc_html__('1C', 'itgalaxy-woocommerce-1c');

        return $columns;
    }

    public function add1cValue($columnData, $column, $id)
    {
        if ($column === 'wc1c') {
            $guid = get_term_meta($id, '_id_1c', true);

            $columnData .= $guid ? esc_html($guid) : esc_html__('no data', 'itgalaxy-woocommerce-1c');
        }

        return $columnData;
    }
}
