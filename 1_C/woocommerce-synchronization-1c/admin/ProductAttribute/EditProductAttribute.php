<?php

namespace Itgalaxy\Wc\Exchange1c\Admin\ProductAttribute;

class EditProductAttribute
{
    public function __construct()
    {
        add_action('woocommerce_after_edit_attribute_fields', [$this, 'showGuidField'], 10);
        add_action('woocommerce_attribute_updated', [$this, 'saveGuidField'], 10, 1);
    }

    /**
     * @return void
     */
    public function showGuidField()
    {
        if (empty($_GET['edit']) || !is_admin() || !function_exists('wc_get_attribute_taxonomies')) {
            return;
        }

        $id = (int) $_GET['edit'];

        if (empty($id)) {
            return;
        }

        $attributes = \wc_get_attribute_taxonomies();
        $attribute = isset($attributes['id:' . $id]) ? $attributes['id:' . $id] : [];
        $value = !empty($attribute) && isset($attribute->id_1c) ? $attribute->id_1c : '';
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="attribute_1c_guid">GUID</label>
            </th>
            <td>
                <input name="attribute_1c_guid" id="attribute_1c_guid" type="text" value="<?php echo esc_attr($value); ?>" />
            </td>
        </tr>
        <?php
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function saveGuidField($id)
    {
        if (!is_admin() || !isset($_POST['attribute_1c_guid'])) {
            return;
        }

        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'woocommerce_attribute_taxonomies',
            ['id_1c' => sanitize_text_field($_POST['attribute_1c_guid'])],
            [
                'attribute_id' => $id,
            ],
            ['%s'],
            ['%d']
        );
    }
}
