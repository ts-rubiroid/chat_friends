<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Base\Parser;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer\Offer;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product\ImagesProduct;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product\ResolverProduct;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\FiredSaveToChangedPriceStockSimpleProducts;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\ProductUnvariable;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\ProductVariablePrices;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\ProductVariableVisibility;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\EndProcessors\SetVariationAttributeToProducts;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

class ParserXml31 extends Parser
{
    /**
     * @throws Exceptions\ProgressException
     * @throws \Exception
     */
    public function parse(\XMLReader $reader)
    {
        while ($reader->read()) {
            // node - "Классификатор"
            $this->parseClassificator($reader);

            if ($reader->name === 'Товары') {
                if (!SettingsHelper::isEmpty('skip_products')) {
                    Logger::log('[skip] enabled `skip_products`');
                }

                $all1cProducts = get_option('all1cProducts', []);

                while (
                    $reader->read()
                    && !($reader->name === 'Товары' && $reader->nodeType === \XMLReader::END_ELEMENT)
                ) {
                    if (!SettingsHelper::isEmpty('skip_products')) {
                        continue;
                    }

                    if (!ResolverProduct::isProductNode($reader)) {
                        continue;
                    }

                    if (!HeartBeat::next('Товар', $reader)) {
                        $count = isset($_SESSION['IMPORT_1C']['heartbeat']['Товар'])
                            ? $_SESSION['IMPORT_1C']['heartbeat']['Товар']
                            : 0;

                        throw new ProgressException("products processing {$count}...");
                    }

                    $element = simplexml_load_string(trim($reader->readOuterXml()));

                    if (!$element instanceof \SimpleXMLElement) {
                        continue;
                    }

                    if (ResolverProduct::customProcessing($element)) {
                        continue;
                    }

                    $element = \apply_filters('itglx_wc1c_product_xml_data', $element);

                    if (ResolverProduct::skipByXml($element)) {
                        continue;
                    }

                    $productID = Product::getSiteProductId($element, ResolverProduct::getNomenclatureGuid($element));

                    // if duplicate product
                    if ($productID && in_array($productID, $_SESSION['IMPORT_1C_PROCESS']['allCurrentProducts'])) {
                        continue;
                    }

                    if (ResolverProduct::isRemoved($element, $productID, $all1cProducts)) {
                        continue;
                    }

                    $productEntry = [
                        'ID' => $productID,
                    ];

                    $isNewProduct = empty($productEntry['ID']);

                    if (!$isNewProduct) {
                        do_action('itglx_wc1c_before_exists_product_info_resolve', $productEntry['ID'], $element);
                    } else {
                        do_action('itglx_wc1c_before_new_product_info_resolve', $element);
                    }

                    $productHash = md5(json_encode((array) $element));

                    if (
                        !$isNewProduct
                        && SettingsHelper::isEmpty('force_update_product')
                        && $productHash == get_post_meta($productEntry['ID'], '_md5', true)
                    ) {
                        $_SESSION['IMPORT_1C_PROCESS']['allCurrentProducts'][] = $productEntry['ID'];
                        $all1cProducts[] = $productEntry['ID'];
                        $currentPostStatus = Product::getStatus($productEntry['ID']);

                        update_option('all1cProducts', array_unique($all1cProducts));

                        // restore product from trash
                        if ($currentPostStatus === 'trash' && !SettingsHelper::isEmpty('restore_products_from_trash')) {
                            \wp_untrash_post($productEntry['ID']);

                            $currentPostStatus = Product::getStatus($productEntry['ID']);

                            Logger::log(
                                '(product) restore from trash, ID - ' . $productEntry['ID'],
                                [get_post_meta($productEntry['ID'], '_id_1c', true)]
                            );
                        }

                        if (
                            !SettingsHelper::isEmpty('more_check_image_changed')
                            && SettingsHelper::isEmpty('skip_post_images')
                        ) {
                            // it is necessary to check the change of images,
                            // since the photo can be changed without changing the file name,
                            // which means the hash matches
                            ImagesProduct::process($element, $productEntry);
                        }

                        Logger::log(
                            '(product) not changed - skip, ID - ' . $productEntry['ID']
                            . ', status - ' . $currentPostStatus,
                            [get_post_meta($productEntry['ID'], '_id_1c', true)]
                        );

                        continue;
                    }

                    $productEntry = Product::mainProductData($element, $productEntry, $productHash);

                    if (empty($productEntry)) {
                        continue;
                    }

                    /**
                     * Fires after processing a "Товар" node.
                     *
                     * Image processing takes place after that.
                     *
                     * @since 1.9.1
                     *
                     * @param int               $productId
                     * @param \SimpleXMLElement $element
                     */
                    do_action('itglx_wc1c_after_product_info_resolve', $productEntry['ID'], $element);

                    $_SESSION['IMPORT_1C_PROCESS']['allCurrentProducts'][] = $productEntry['ID'];
                    $all1cProducts[] = $productEntry['ID'];

                    update_option('all1cProducts', array_unique($all1cProducts));

                    // is new or not disabled image data processing
                    if ($isNewProduct || SettingsHelper::isEmpty('skip_post_images')) {
                        ImagesProduct::process($element, $productEntry);
                    }
                }

                delete_option('product_cat_children');
                wp_cache_flush();
            }

            if ($reader->name === 'ПакетПредложений') {
                if (!isset($_SESSION['IMPORT_1C_PROCESS']['allCurrentOffers'])) {
                    $_SESSION['IMPORT_1C_PROCESS']['allCurrentOffers'] = [];
                }

                if (!isset($_SESSION['IMPORT_1C']['offers_parse'])) {
                    if (!SettingsHelper::isEmpty('skip_offers')) {
                        Logger::log('[skip] enabled `skip_offers`');
                    }

                    while (
                        $reader->read()
                        && !($reader->name === 'ПакетПредложений' && $reader->nodeType === \XMLReader::END_ELEMENT)
                    ) {
                        // enabled skip offers
                        if (!SettingsHelper::isEmpty('skip_offers')) {
                            continue;
                        }

                        if (!Offer::isOfferNode($reader)) {
                            continue;
                        }

                        Offer::process($reader, $this->rate);
                    }

                    $_SESSION['IMPORT_1C']['offers_parse'] = true;
                }

                $baseName = basename(RootProcessStarter::getCurrentExchangeFileAbsPath());

                // rests are the last processing file in protocol - is the stock data
                if (strpos($baseName, 'rests') !== false) {
                    FiredSaveToChangedPriceStockSimpleProducts::process();
                    ProductUnvariable::process();
                    ProductVariablePrices::process();
                    ProductVariableVisibility::process();
                }

                SetVariationAttributeToProducts::process();
            } // end 'Предложения'
        } // end parse

        \wp_defer_term_counting(false);
    }
}
