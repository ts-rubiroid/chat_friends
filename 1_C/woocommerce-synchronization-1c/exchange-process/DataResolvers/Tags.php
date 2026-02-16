<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Term;

class Tags
{
    /**
     * Processing and save info by tags.
     *
     * @param \XMLReader $reader
     *
     * @return void
     *
     * @throws ProgressException
     */
    public static function process(\XMLReader $reader)
    {
        $numberOfTags = 0;

        if (!isset($_SESSION['IMPORT_1C']['numberOfTags'])) {
            $_SESSION['IMPORT_1C']['numberOfTags'] = 0;
        }

        if (!isset($_SESSION['IMPORT_1C']['productTags'])) {
            $_SESSION['IMPORT_1C']['productTags'] = [];
        }

        while (
            $reader->read()
            && !($reader->name === 'Метки' && $reader->nodeType === \XMLReader::END_ELEMENT)
        ) {
            /*
             * Example xml structure
             * position - Классификатор -> Метки
             *
            <Метки>
                <Метка>
                    <Ид>f108c911-3bca-11eb-841f-ade4b337caca</Ид>
                    <Наименование>Метка 1</Наименование>
                </Метка>
                <Метка>
                    <Ид>f108c912-3bca-11eb-841f-ade4b337caca</Ид>
                    <Наименование>Метка 2</Наименование>
                </Метка>
                <Метка>
                    <Ид>f108c913-3bca-11eb-841f-ade4b337caca</Ид>
                    <Наименование>Метка 3</Наименование>
                </Метка>
             </Метки>
            */
            if ($reader->name !== 'Метка' && $reader->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }

            if (HeartBeat::limitIsExceeded()) {
                throw new ProgressException('tags processing...');
            }

            ++$numberOfTags;

            if ($numberOfTags < $_SESSION['IMPORT_1C']['numberOfTags']) {
                continue;
            }

            $element = simplexml_load_string(trim($reader->readOuterXml()));

            // ignore invalid
            if (!isset($element->Ид)) {
                unset($element);

                continue;
            }

            $tag = Term::getTermIdByMeta((string) $element->Ид);

            if (!$tag) {
                $tag = apply_filters('itglx_wc1c_find_product_tag_term_id', $tag, $element);

                if ($tag) {
                    \update_term_meta($tag, '_id_1c', (string) $element->Ид);
                }
            }

            $tagEntry = [
                'name' => \wp_slash(trim(wp_strip_all_tags((string) $element->Наименование))),
            ];

            if ($tag) {
                \wp_update_term($tag, 'product_tag', $tagEntry);
            } else {
                $result = \wp_insert_term($tagEntry['name'], 'product_tag');

                if (!is_wp_error($result)) {
                    // default meta value by ordering
                    \update_term_meta($result['term_id'], 'order', 0);
                    \update_term_meta($result['term_id'], '_id_1c', (string) $element->Ид);

                    $tag = $result['term_id'];
                }
            }

            if ($tag) {
                $_SESSION['IMPORT_1C']['productTags'][(string) $element->Ид] = $tag;
            }

            $_SESSION['IMPORT_1C']['numberOfTags'] = $numberOfTags;
        }

        self::setParsed();
    }

    /**
     * Checking if the reader is in the position with tag node.
     *
     * @param \XMLReader $reader
     *
     * @return bool
     */
    public static function isTagNode(\XMLReader $reader)
    {
        return in_array($reader->name, ['Метки', 'Метка'], true);
    }

    /**
     * Allows you to check if tags have already been processed or not.
     *
     * @return bool
     */
    public static function isParsed()
    {
        return isset($_SESSION['IMPORT_1C']['tagsIsParse']);
    }

    /**
     * Sets the flag that tags have been processed.
     *
     * @return void
     */
    public static function setParsed()
    {
        $_SESSION['IMPORT_1C']['tagsIsParse'] = true;
    }
}
