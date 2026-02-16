<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProtocolException;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class ProductAttributeEntity
{
    /**
     * @param string $label
     * @param string $name
     * @param string $guid
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function insert($label, $name, $guid)
    {
        global $wpdb;

        $naturalName = self::getUniqueAttributeName($label);

        $newAttributeParams = [
            'attribute_label' => $label,
            'attribute_name' => $naturalName ? $naturalName : $name,
            'attribute_type' => 'select',
            'attribute_public' => SettingsHelper::isEmpty('attribute_create_enable_public') ? 0 : 1,
            'attribute_orderby' => 'menu_order',
            'id_1c' => $guid,
        ];

        /**
         * Filters a set of parameters when creating a new attribute.
         *
         * @since 1.80.1
         *
         * @param array $newAttributeParams
         */
        $newAttributeParams = \apply_filters('itglx_wc1c_create_product_attribute_args', $newAttributeParams);

        $wpdb->insert("{$wpdb->prefix}woocommerce_attribute_taxonomies", $newAttributeParams);

        // maybe error when insert processing, for example, non exists column `id_1c`
        if (empty($wpdb->insert_id)) {
            throw new ProtocolException("LAST ERROR - {$wpdb->last_error}, LAST QUERY - {$wpdb->last_query}");
        }

        \do_action('woocommerce_attribute_added', $wpdb->insert_id, $newAttributeParams);

        \wp_schedule_single_event(time(), 'woocommerce_flush_rewrite_rules');

        // Clear transients.
        \delete_transient('wc_attribute_taxonomies');

        if (
            class_exists('\\WC_Cache_Helper')
            && method_exists('\\WC_Cache_Helper', 'invalidate_cache_group')
        ) {
            \WC_Cache_Helper::invalidate_cache_group('woocommerce-attributes');
        }

        \delete_option('pa_' . $newAttributeParams['attribute_name'] . '_children');

        return $newAttributeParams;
    }

    /**
     * Retrieving an attribute record at `guid`.
     *
     * @param string $value Attribute `guid`.
     *
     * @return null|object
     */
    public static function get($value)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}woocommerce_attribute_taxonomies` WHERE `id_1c` = %s",
                (string) $value
            )
        );
    }

    public static function getByName($value)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}woocommerce_attribute_taxonomies` WHERE `attribute_name` = %s",
                (string) $value
            )
        );
    }

    public static function getByLabel($value)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}woocommerce_attribute_taxonomies` WHERE `attribute_label` = %s",
                (string) $value
            )
        );
    }

    public static function update($attributeUpdate, $attributeID)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'woocommerce_attribute_taxonomies',
            $attributeUpdate,
            [
                'attribute_id' => $attributeID,
            ]
        );

        // Clear transients.
        \delete_transient('wc_attribute_taxonomies');

        if (
            class_exists('\\WC_Cache_Helper')
            && method_exists('\\WC_Cache_Helper', 'invalidate_cache_group')
        ) {
            \WC_Cache_Helper::invalidate_cache_group('woocommerce-attributes');
        }
    }

    private static function getUniqueAttributeName($label)
    {
        $name = \wc_sanitize_taxonomy_name(self::sanitizeTransliterationName($label));

        // if for some reason an empty result is received, then they should immediately return a negative result
        if (empty($name)) {
            return false;
        }

        // https://developer.wordpress.org/reference/functions/register_taxonomy/
        $maxNameLength = 32;

        // WooCommerce added prefix - `pa_`
        $maxNameLength -= 3;

        // count value up to 99 - `-00`
        $maxNameLength -= 3;

        if (strlen($name) > $maxNameLength) {
            $name = substr($name, 0, $maxNameLength);
        }

        /*
         * the second call to clear a possible incorrect result,
         * for example, it might get `opisanie-dlya-sluzhebnogo-`, but it should be `opisanie-dlya-sluzhebnogo`
         */
        $name = \wc_sanitize_taxonomy_name($name);
        $resolvedName = $name;
        $count = 0;
        $attribute = self::getByName($resolvedName);

        while ($attribute && $count < 100) {
            ++$count;
            $resolvedName = $name . '-' . $count;
            $attribute = self::getByName($resolvedName);
        }

        if ($count > 99) {
            return false;
        }

        return $resolvedName;
    }

    private static function sanitizeTransliterationName($title)
    {
        $iso9Table = [
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Ѓ' => 'G',
            'Ґ' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'YO',
            'Є' => 'YE',
            'Ж' => 'ZH',
            'З' => 'Z',
            'Ѕ' => 'Z',
            'И' => 'I',
            'Й' => 'J',
            'Ј' => 'J',
            'І' => 'I',
            'Ї' => 'YI',
            'К' => 'K',
            'Ќ' => 'K',
            'Л' => 'L',
            'Љ' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'Њ' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ў' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'TS',
            'Ч' => 'CH',
            'Џ' => 'DH',
            'Ш' => 'SH',
            'Щ' => 'SHH',
            'Ъ' => '',
            'Ы' => 'Y',
            'Ь' => '',
            'Э' => 'E',
            'Ю' => 'YU',
            'Я' => 'YA',
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'ѓ' => 'g',
            'ґ' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'yo',
            'є' => 'ye',
            'ж' => 'zh',
            'з' => 'z',
            'ѕ' => 'z',
            'и' => 'i',
            'й' => 'j',
            'ј' => 'j',
            'і' => 'i',
            'ї' => 'yi',
            'к' => 'k',
            'ќ' => 'k',
            'л' => 'l',
            'љ' => 'l',
            'м' => 'm',
            'н' => 'n',
            'њ' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ў' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'ts',
            'ч' => 'ch',
            'џ' => 'dh',
            'ш' => 'sh',
            'щ' => 'shh',
            'ъ' => '',
            'ы' => 'y',
            'ь' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
        ];

        $geo2lat = [
            'ა' => 'a',
            'ბ' => 'b',
            'გ' => 'g',
            'დ' => 'd',
            'ე' => 'e',
            'ვ' => 'v',
            'ზ' => 'z',
            'თ' => 'th',
            'ი' => 'i',
            'კ' => 'k',
            'ლ' => 'l',
            'მ' => 'm',
            'ნ' => 'n',
            'ო' => 'o',
            'პ' => 'p',
            'ჟ' => 'zh',
            'რ' => 'r',
            'ს' => 's',
            'ტ' => 't',
            'უ' => 'u',
            'ფ' => 'ph',
            'ქ' => 'q',
            'ღ' => 'gh',
            'ყ' => 'qh',
            'შ' => 'sh',
            'ჩ' => 'ch',
            'ც' => 'ts',
            'ძ' => 'dz',
            'წ' => 'ts',
            'ჭ' => 'tch',
            'ხ' => 'kh',
            'ჯ' => 'j',
            'ჰ' => 'h',
        ];

        $hy = [
            'և' => 'ev', 'ու' => 'u', '[\s\t]+?ո' => '\svo',
            'Ա' => 'A', 'Բ' => 'B', 'Գ' => 'G', 'Դ' => 'D', 'Ե' => 'Ye', 'Զ' => 'Z', 'Է' => 'E',
            'Ը' => 'Eh', 'Թ' => 'Th', 'Ժ' => 'Zh', 'Ի' => 'I', 'Լ' => 'L', 'Խ' => 'X', 'Ծ' => 'Tc',
            'Կ' => 'K', 'Հ' => 'H', 'Ձ' => 'Dz', 'Ղ' => 'Gh', 'Ճ' => 'Tch', 'Մ' => 'M', 'Յ' => 'Y',
            'Ն' => 'N', 'Շ' => 'Sh', 'Ո' => 'Vo', 'Չ' => 'Ch', 'Պ' => 'P', 'Ջ' => 'J', 'Ռ' => 'R',
            'Ս' => 'S', 'Վ' => 'V', 'Տ' => 'T', 'Ր' => 'R', 'Ց' => 'C', 'Փ' => 'Ph', 'Ք' => 'Kh',
            'Օ' => 'O', 'Ֆ' => 'F',
            'ա' => 'a', 'բ' => 'b', 'գ' => 'g', 'դ' => 'd', 'ե' => 'e', 'զ' => 'z', 'է' => 'e',
            'ը' => 'eh', 'թ' => 'th', 'ժ' => 'zh', 'ի' => 'i', 'լ' => 'l', 'խ' => 'x', 'ծ' => 'tc',
            'կ' => 'k', 'հ' => 'h', 'ձ' => 'dz', 'ղ' => 'gh', 'ճ' => 'tch', 'մ' => 'm', 'յ' => 'y',
            'ն' => 'n', 'շ' => 'sh', 'ո' => 'o', 'չ' => 'ch', 'պ' => 'p', 'ջ' => 'j', 'ռ' => 'r',
            'ս' => 's', 'վ' => 'v', 'տ' => 't', 'ր' => 'r', 'ց' => 'c', 'փ' => 'ph', 'ք' => 'kh',
            'օ' => 'o', 'ֆ' => 'f',
            '№' => '#', '—' => '-', '«' => '', '»' => '', '…' => '',
        ];

        $iso9Table = array_merge($iso9Table, $geo2lat, $hy);
        $locale = \get_locale();

        switch ($locale) {
            case 'bg_BG':
                $iso9Table['Щ'] = 'SHT';
                $iso9Table['щ'] = 'sht';
                $iso9Table['Ъ'] = 'A';
                $iso9Table['ъ'] = 'a';
                break;
            case 'uk':
            case 'uk_ua':
                $iso9Table['И'] = 'Y';
                $iso9Table['и'] = 'y';
                break;
        }

        $title = strtr($title, \apply_filters('ctl_table', $iso9Table));

        if (function_exists('iconv')) {
            $title = iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $title);
        }

        $title = preg_replace("/[^A-Za-z0-9'_\\-\\.]/", '-', $title);
        $title = preg_replace('/\-+/', '-', $title);
        $title = preg_replace('/^-+/', '', $title);

        return preg_replace('/-+$/', '', $title);
    }
}
