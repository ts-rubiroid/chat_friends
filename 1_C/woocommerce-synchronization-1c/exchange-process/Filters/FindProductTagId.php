<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

class FindProductTagId
{
    private static $instance = false;

    private function __construct()
    {
        \add_filter('itglx_wc1c_find_product_tag_term_id', [$this, 'findByName'], 10, 2);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function findByName($termID, $element)
    {
        if ((int) $termID) {
            return $termID;
        }

        if (empty($element->Наименование)) {
            return $termID;
        }

        $terms = \get_terms(
            [
                'taxonomy' => 'product_tag',
                'parent' => 0,
                'name' => \wp_slash(trim(\wp_strip_all_tags((string) $element->Наименование))),
                'hide_empty' => false,
                'fields' => 'ids',
                // find only terms without guid
                'meta_query' => [
                    [
                        'key' => '_id_1c',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]
        );

        if (\is_wp_error($terms) || !$terms) {
            return $termID;
        }

        // ignore if results more one
        if (count($terms) > 1) {
            return $termID;
        }

        return $terms[0];
    }
}
