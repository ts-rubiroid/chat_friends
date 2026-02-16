<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\Filters;

/**
 * Using a filter to check whether an offer has been deleted or not according to XML data.
 */
class OfferIsRemoved
{
    private static $instance = false;

    /**
     * Create new instance.
     *
     * @see https://developer.wordpress.org/reference/functions/add_filter/
     *
     * @return void
     */
    private function __construct()
    {
        \add_filter('itglx/wc1c/catalog/import/offer/simple/is-removed', [$this, 'process'], 10, 2);
        \add_filter('itglx_wc1c_variation_offer_is_removed', [$this, 'process'], 10, 2);
    }

    /**
     * Returns an instance of a class or creates a new instance if it doesn't exist.
     *
     * @return OfferIsRemoved
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Filter callback. Checking the signs of offer removal.
     *
     * Example xml structure (position ПакетПредложений -> Предложения -> Предложение)
     *
     * ```xml
     * <Предложение>
     *    <Статус>Удален</Статус>
     *    ...
     * </Предложение>
     * <Предложение Статус="Удален">
     *    ...
     * </Предложение>
     * <Предложение>
     *    <ПометкаУдаления>true</ПометкаУдаления>
     *    ...
     * </Предложение>
     * <Предложение>
     *    <ПометкаУдаления>Да</ПометкаУдаления>
     *    ...
     * </Предложение>
     *
     * @param bool              $isRemoved
     * @param \SimpleXMLElement $element   Node object "Предложение".
     *
     * @return bool
     */
    public function process($isRemoved, \SimpleXMLElement $element)
    {
        if ($isRemoved) {
            return $isRemoved;
        }

        if (isset($element->Статус) && (string) $element->Статус === 'Удален') {
            return true;
        }

        if ((string) $element->ПометкаУдаления && in_array((string) $element->ПометкаУдаления, ['true', 'Да'], true)) {
            return true;
        }

        if (isset($element['Статус']) && (string) $element['Статус'] === 'Удален') {
            return true;
        }

        return $isRemoved;
    }
}
