<?php

namespace Itgalaxy\Wc\Exchange1c\ExchangeProcess\DataResolvers\Offer;

use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Entities\ImageEntity;
use Itgalaxy\Wc\Exchange1c\ExchangeProcess\Helpers\ProductVariation;
use Itgalaxy\Wc\Exchange1c\Includes\Helper;
use Itgalaxy\Wc\Exchange1c\Includes\Logger;

class VariationOfferImages
{
    /**
     * @param \SimpleXMLElement $element
     * @param int               $variationID
     * @param int               $productID
     */
    public static function process($element, $variationID, $productID)
    {
        $dirName = apply_filters('itglx_wc1c_root_image_directory', Helper::getTempPath() . '/');
        $oldImages = get_post_meta($variationID, '_old_images', true);

        if (!is_array($oldImages)) {
            $oldImages = [];
        }

        $attachmentIds = [];

        if (isset($element->Картинка) && (string) $element->Картинка) {
            Logger::log('[start] images - ' . $variationID);
            $images = [];

            foreach ($element->Картинка as $image) {
                $images[] = (string) $image;
            }

            ProductVariation::saveMetaValue($variationID, '_old_images', $images, $productID);

            // delete images that do not exist in the current set
            foreach ($oldImages as $oldImage) {
                $attachID = ImageEntity::findByMeta($oldImage);
                $imageSrcPath = ImageEntity::srcResultPath($dirName, $oldImage, $variationID, $element);
                $removeImage = false;

                if ($attachID && !in_array($oldImage, $images, true)) {
                    $removeImage = true;
                } elseif (
                    $attachID
                    && file_exists($imageSrcPath)
                    && sha1_file($imageSrcPath) !== get_post_meta($attachID, '_1c_image_sha_hash', true)
                ) {
                    $removeImage = true;
                }

                if ($removeImage) {
                    // clean product thumbnail if removed
                    if ((int) $attachID === (int) get_post_meta($variationID, '_thumbnail_id', true)) {
                        ProductVariation::saveMetaValue($variationID, '_thumbnail_id', '', $productID);
                    }

                    ImageEntity::remove($attachID, $variationID);
                }
            }

            foreach ($images as $image) {
                $attachID = ImageEntity::findByMeta($image);

                if ($attachID && !\is_wp_error($attachID)) {
                    $attachmentIds[] = $attachID;
                } else {
                    $imageSrcPath = ImageEntity::srcResultPath($dirName, $image, $variationID, $element);

                    if (file_exists($imageSrcPath)) {
                        $attachID = ImageEntity::insert($image, $imageSrcPath, $variationID);

                        if ($attachID) {
                            $attachmentIds[] = $attachID;
                        }
                    }
                }
            }

            // distribution of the current set of images
            if (!empty($attachmentIds)) {
                /**
                 * Take the first ID to set it as a variation thumbnail.
                 */
                $attachID = reset($attachmentIds);

                ProductVariation::saveMetaValue($variationID, '_thumbnail_id', $attachID, $productID);
                Logger::log('(image variation) set thumbnail image for ID - ' . $variationID, [$attachID]);
            }

            Logger::log('[end] images - ' . $variationID);
        }
        // the current data does not contain information about the image, but it was before, so it should be deleted
        elseif ($oldImages) {
            Logger::log(
                '(image variation) removed images (the current data does not contain information) for ID - '
                . $variationID,
                [(string) $element->Ид]
            );

            self::removeImages($oldImages, $variationID, $productID);
        }

        /**
         * Fires after image processing for variation.
         *
         * Fires in any case, even if the resulting image set is empty as a result of processing. If the set was
         * not empty earlier, then based on the empty data, it is possible to make decisions about the necessary
         * cleaning actions.
         *
         * @since 1.93.0
         *
         * @param int               $variationID   Product variation id.
         * @param int[]             $attachmentIds The array contains the IDs of all images. If the set is not empty, then the
         *                                         first element is already set as a variation image.
         * @param \SimpleXMLElement $element       'Предложение' node object.
         */
        \do_action('itglx_wc1c_product_variation_images', $variationID, $attachmentIds, $element);
    }

    /**
     * @param string[] $oldImages
     * @param int      $variationID
     * @param int      $productID
     */
    private static function removeImages($oldImages, $variationID, $productID)
    {
        ProductVariation::saveMetaValue($variationID, '_old_images', [], $productID);
        ProductVariation::saveMetaValue($variationID, '_thumbnail_id', '', $productID);

        // delete a known set of images
        foreach ($oldImages as $oldImage) {
            $attachID = ImageEntity::findByMeta($oldImage);

            if ($attachID) {
                ImageEntity::remove($attachID, $variationID);
            }
        }
    }
}
