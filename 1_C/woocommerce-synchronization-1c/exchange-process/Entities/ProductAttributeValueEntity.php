<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class ProductAttributeValueEntity
{
    /**
     * @param string $name
     * @param string $taxonomy
     * @param string $alternativeSlug
     * @param bool   $ignoreLastError Default: false.
     *
     * @return array|\WP_Error
     *
     * @throws \Exception
     *
     * @see https://developer.wordpress.org/reference/functions/wp_insert_term/
     * @see https://developer.wordpress.org/reference/functions/wp_unique_term_slug/
     */
    public static function insert($name, $taxonomy, $alternativeSlug, $ignoreLastError = false)
    {
        $valueTerm = \wp_insert_term(
            \wp_slash($name),
            $taxonomy,
            [
                'slug' => \wp_unique_term_slug(
                    \sanitize_title($name),
                    (object) [
                        'taxonomy' => $taxonomy,
                        'parent' => 0,
                    ]
                ),
                'description' => '',
                'parent' => 0,
            ]
        );

        if (\is_wp_error($valueTerm)) {
            $valueTerm = \wp_insert_term(
                \wp_slash($name),
                $taxonomy,
                [
                    'slug' => $alternativeSlug,
                    'description' => '',
                    'parent' => 0,
                ]
            );
        }

        if (!$ignoreLastError && \is_wp_error($valueTerm)) {
            throw new ProtocolException(
                'ERROR ADD ATTRIBUTE VALUE - '
                . $valueTerm->get_error_message()
                . ', tax - '
                . $taxonomy
                . ', value - '
                . $name
            );
        }

        Logger::log('(attribute value) added new - ' . $taxonomy, $name);

        return $valueTerm;
    }

    /**
     * @param string $name
     * @param int    $termID
     * @param string $taxonomy
     *
     * @see https://developer.wordpress.org/reference/functions/wp_update_term/
     */
    public static function update($name, $termID, $taxonomy)
    {
        \wp_update_term(
            $termID,
            $taxonomy,
            [
                'name' => \wp_slash($name),
                'parent' => 0,
            ]
        );
    }
}
