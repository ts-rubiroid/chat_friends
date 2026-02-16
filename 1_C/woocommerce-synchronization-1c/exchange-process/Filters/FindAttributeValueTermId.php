<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class FindAttributeValueTermId
{
    public function __construct()
    {
        if (!SettingsHelper::isEmpty('find_exists_attribute_value_by_name')) {
            \add_filter('itglx_wc1c_find_exists_product_attribute_value_term_id', [$this, 'findByName'], 10, 3);
        }
    }

    /**
     * @param int               $termID
     * @param \SimpleXMLElement $element
     * @param string            $taxonomy
     *
     * @return int
     */
    public function findByName($termID, $element, $taxonomy)
    {
        if ((int) $termID || empty($element->Значение)) {
            return $termID;
        }

        $terms = \get_terms(
            [
                'taxonomy' => $taxonomy,
                'parent' => 0,
                'name' => \wp_slash(trim(\wp_strip_all_tags((string) $element->Значение))),
                'hide_empty' => false,
                'orderby' => 'name',
                'fields' => 'ids',
            ]
        );

        if (\is_wp_error($terms) || !$terms) {
            return $termID;
        }

        return $terms[0];
    }
}
