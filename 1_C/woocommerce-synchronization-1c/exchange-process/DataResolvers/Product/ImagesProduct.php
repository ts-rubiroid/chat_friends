<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Product;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ImageEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Exceptions\ProgressException;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\HeartBeat;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\Product;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;
use Itgalaxy\Wc\Exchange1c\Includes\SettingsHelper;

/**
 * Parsing and saving images of the specified product.
 */
class ImagesProduct
{
    /**
     * @param \SimpleXMLElement $element
     * @param array             $productEntry
     *
     * @return void
     *
     * @throws ProgressException
     */
    public static function process($element, $productEntry)
    {
        if (isset($element->Картинка) && (string) $element->Картинка) {
            Logger::log('[start] images - ' . $productEntry['ID']);

            $images = [];

            foreach ($element->Картинка as $image) {
                /**
                 * Ignore duplicates, since in some configurations erroneous behavior is encountered with the fact
                 * that the content includes several nodes with the same file, although there should be one.
                 */
                if (in_array((string) $image, $images, true)) {
                    continue;
                }

                $images[] = (string) $image;
            }

            /**
             * Filters the set of image file paths that is obtained from the current product node.
             *
             * @since 1.123.0
             *
             * @param array             $images
             * @param \SimpleXMLElement $element
             * @param array             $productEntry
             */
            $images = \apply_filters('itglx/wc1c/catalog/import/product/images-file-list', $images, $element, $productEntry);

            $_SESSION['imagesProgress'] = [
                'step' => 'processOldSet',
            ];

            self::progress($element, $productEntry, $images);

            return;
        }

        $oldImages = \get_post_meta($productEntry['ID'], '_old_images', true);

        // the current data does not contain information about the image, but it was before, so it should be deleted
        if ($oldImages) {
            if (apply_filters('itglx_wc1c_do_not_delete_images_if_xml_does_not_contain', false, $productEntry['ID'])) {
                return;
            }

            Logger::log(
                '(image) removed images (the current data does not contain information) for ID - '
                . $productEntry['ID'],
                [get_post_meta($productEntry['ID'], '_id_1c', true)]
            );

            self::removeImages($oldImages, $productEntry);
        }
    }

    /**
     * @return void
     *
     * @throws ProgressException
     */
    public static function continueProgress()
    {
        Logger::log('[progress] images continue - ' . $_SESSION['imagesProgress']['productEntry']['ID']);

        self::progress(
            // previously saved `xml`, now we need to restore the object
            simplexml_load_string($_SESSION['imagesProgress']['element']),
            $_SESSION['imagesProgress']['productEntry'],
            $_SESSION['imagesProgress']['images']
        );
    }

    /**
     * @return bool
     */
    public static function hasInProgress()
    {
        return isset($_SESSION['imagesProgress']);
    }

    /**
     * @param \SimpleXMLElement $element
     * @param array             $productEntry
     * @param array             $images
     *
     * @return void
     *
     * @throws ProgressException
     */
    private static function progress($element, $productEntry, $images)
    {
        $dirName = apply_filters('itglx_wc1c_root_image_directory', Helper::getTempPath() . '/');
        $oldImages = \get_post_meta($productEntry['ID'], '_old_images', true);

        if (!is_array($oldImages)) {
            $oldImages = [];
        }

        if ($_SESSION['imagesProgress']['step'] === 'processOldSet') {
            self::processOldSet($oldImages, $images, $dirName, $productEntry, $element);
            $_SESSION['imagesProgress']['step'] = 'processCurrentSet';
        }

        $attachmentIds = [];

        foreach ($images as $image) {
            if (HeartBeat::limitIsExceeded()) {
                self::sessionFill($element, $productEntry, $images);
                Logger::log('(image) progress interrupt - ' . $productEntry['ID']);

                throw new ProgressException("(image) progress interrupt - {$productEntry['ID']}...");
            }

            $attachID = ImageEntity::findByMeta($image);

            if ($attachID && !\is_wp_error($attachID)) {
                $attachmentIds[] = $attachID;
            } else {
                $imageSrcPath = ImageEntity::srcResultPath($dirName, $image, $productEntry['ID'], $element);

                if (file_exists($imageSrcPath)) {
                    $attachID = ImageEntity::insert($image, $imageSrcPath, $productEntry['ID']);

                    if ($attachID) {
                        $attachmentIds[] = $attachID;
                    }
                }
            }
        }

        if (empty($attachmentIds)) {
            Logger::log('[end] images, no attachments - ' . $productEntry['ID']);
            Product::saveMetaValue($productEntry['ID'], '_old_images', $images);
            self::sessionClear();

            return;
        }

        $gallery = [];
        $hasThumbnail = false;

        // distribution of the current set of images
        foreach ($attachmentIds as $attachID) {
            if (
                !SettingsHelper::isEmpty('set_category_thumbnail_by_product')
                && !empty($productEntry['productCatList'])
            ) {
                foreach ($productEntry['productCatList'] as $termID) {
                    if (!\get_term_meta((int) $termID, 'thumbnail_id', true)) {
                        \update_term_meta((int) $termID, 'thumbnail_id', $attachID);
                    }
                }
            }

            if (!$hasThumbnail) {
                Product::saveMetaValue($productEntry['ID'], '_thumbnail_id', $attachID);
                $hasThumbnail = true;
                Logger::log('(image) set thumbnail image for ID - ' . $productEntry['ID'], [$attachID]);
            } else {
                $gallery[] = $attachID;
            }
        }

        // setting gallery images
        if (!empty($gallery)) {
            Product::saveMetaValue($productEntry['ID'], '_product_image_gallery', implode(',', $gallery));
            Logger::log('(image) set gallery for ID - ' . $productEntry['ID'], $gallery);
        }
        // if in the current set there are no images for the gallery, but before they were - delete
        elseif (count($oldImages) > 1) {
            Product::saveMetaValue($productEntry['ID'], '_product_image_gallery', '');
            Logger::log('(image) clean gallery for ID - ' . $productEntry['ID']);
        }

        Product::saveMetaValue($productEntry['ID'], '_old_images', $images);
        self::sessionClear();
        Logger::log('[end] images - ' . $productEntry['ID']);
    }

    private static function processOldSet($oldImages, $images, $dirName, $productEntry, $element)
    {
        if (empty($oldImages)) {
            return;
        }

        // delete images that do not exist in the current set
        foreach ($oldImages as $oldImage) {
            $attachID = ImageEntity::findByMeta($oldImage);
            $imageSrcPath = ImageEntity::srcResultPath($dirName, $oldImage, $productEntry['ID'], $element);
            $removeImage = false;

            if ($attachID && !in_array($oldImage, $images, true)) {
                $removeImage = true;
            } elseif (
                $attachID
                && file_exists($imageSrcPath)
                && sha1_file($imageSrcPath) !== \get_post_meta($attachID, '_1c_image_sha_hash', true)
            ) {
                $removeImage = true;
            }

            if (!$removeImage) {
                continue;
            }

            // clean product thumbnail if removed
            if ((int) $attachID === (int) \get_post_meta($productEntry['ID'], '_thumbnail_id', true)) {
                Product::saveMetaValue($productEntry['ID'], '_thumbnail_id', '');
            }

            // clean category thumbnail if removed
            if (!SettingsHelper::isEmpty('set_category_thumbnail_by_product') && !empty($productEntry['productCatList'])) {
                foreach ($productEntry['productCatList'] as $termID) {
                    if ((int) $attachID === (int) \get_term_meta((int) $termID, 'thumbnail_id', true)) {
                        \update_term_meta((int) $termID, 'thumbnail_id', '');
                    }
                }
            }

            ImageEntity::remove($attachID, $productEntry['ID']);
        }
    }

    private static function removeImages($oldImages, $productEntry)
    {
        Product::saveMetaValue($productEntry['ID'], '_old_images', []);
        Product::saveMetaValue($productEntry['ID'], '_thumbnail_id', '');

        if (count($oldImages) > 1) {
            Product::saveMetaValue($productEntry['ID'], '_product_image_gallery', '');
        }

        // delete a known set of images
        foreach ($oldImages as $oldImage) {
            $attachID = ImageEntity::findByMeta($oldImage);

            if (!$attachID) {
                continue;
            }

            // clean category thumbnail if removed
            if (!SettingsHelper::isEmpty('set_category_thumbnail_by_product') && !empty($productEntry['productCatList'])) {
                foreach ($productEntry['productCatList'] as $termID) {
                    if ((int) $attachID === (int) \get_term_meta((int) $termID, 'thumbnail_id', true)) {
                        \update_term_meta((int) $termID, 'thumbnail_id', '');
                    }
                }
            }

            ImageEntity::remove($attachID, $productEntry['ID']);
        }
    }

    /**
     * @param \SimpleXMLElement $element
     * @param array             $productEntry
     * @param array             $images
     */
    private static function sessionFill($element, $productEntry, $images)
    {
        // already filled
        if (isset($_SESSION['imagesProgress']['element'])) {
            return;
        }

        $_SESSION['imagesProgress']['productEntry'] = $productEntry;
        $_SESSION['imagesProgress']['images'] = $images;

        // we cannot save the object as `SimpleXMLElement` cannot be serialized
        $_SESSION['imagesProgress']['element'] = $element->asXML();
    }

    private static function sessionClear()
    {
        if (!isset($_SESSION['imagesProgress'])) {
            return;
        }

        unset($_SESSION['imagesProgress']);
    }
}
