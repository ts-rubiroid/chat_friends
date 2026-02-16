<?php

namespace Itgalaxy\Wc\Exchange1c\Includes\Actions;

class WooCommerceAttributeDeleted
{
    private static $instance = false;

    private function __construct()
    {
        add_action('woocommerce_attribute_deleted', [$this, 'cleanOptionDataByRemovedAttribute'], 10, 3);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * The method clears the data cache of the links between the unloaded property and the attribute.
     *
     * @param int|string $id       Attribute id
     * @param string     $name     Attribute name
     * @param string     $taxonomy Attribute taxonomy
     */
    public function cleanOptionDataByRemovedAttribute($id, $name, $taxonomy)
    {
        if (empty($taxonomy)) {
            return;
        }

        $options = get_option('all_product_options', []);

        if (empty($options)) {
            return;
        }

        $resultOptionsData = $options;

        foreach ($options as $key => $option) {
            if (isset($option['taxName']) && $option['taxName'] === $taxonomy) {
                unset($resultOptionsData[$key]);
            }
        }

        if ($resultOptionsData !== $options) {
            update_option('all_product_options', $resultOptionsData);
        }
    }
}
