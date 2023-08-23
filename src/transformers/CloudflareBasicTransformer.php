<?php

declare(strict_types=1);
/**
 * Image Guru plugin for Craft CMS 4.x
 *
 * Streamline your Image Transforms
 *
 * @link      https://zaengle.com
 * @copyright Copyright (c) 2022 Zaengle Corp
 */

namespace zaengle\imageguru\transformers;

use craft\base\imagetransforms\ImageTransformerInterface;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\helpers\App;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\models\ImageTransform as CraftImageTransform;

use zaengle\imageguru\base\BaseCloudflareTransformer;
use zaengle\imageguru\ImageGuru;
use zaengle\imageguru\models\CloudflareImageTransform;
use zaengle\imageguru\models\VolumeTransformSettings;

/**
 * CloudflareBasicTransformer transforms image assets using Cloudflare's Image Resizing service
 *
 * @author Zaengle Corp. <hello@zaengle.com>
 * @since 1.0.0
 * @see https://developers.cloudflare.com/images/image-resizing/ CF service documentation
 */
class CloudflareBasicTransformer extends BaseCloudflareTransformer
{
    /**
     * The 'special' segment to add to CF URLs to trigger parsing of transform params by CF
     * image resizing
     */
    public const CF_PATH_PREFIX = '/cdn-cgi/image/';

    // Public Methods
    // =========================================================================

    /**
     * Returns the URL for an image transform
     *
     * @param Asset $asset
     * @param CraftImageTransform|CloudflareImageTransform $imageTransform
     * @param bool $immediately Ignored/unused, declaration required for compatibility with default transformer
     * @return string The transform URL
     * @throws ImageTransformException
     */
    public function getTransformUrl(Asset $asset, CraftImageTransform|CloudflareImageTransform $imageTransform, bool $immediately): string
    {
        $imageTransform = TransformHelper::normalizeTransform($imageTransform);
        $volumeSettings = ImageGuru::getInstance()->transformer->getTransformerSettingsByAsset($asset);

        $params = self::mergeParams($imageTransform, $asset, $volumeSettings);

        return self::buildUrl($asset, $volumeSettings->transformBaseUrl, $params);
    }

    /**
     * Normalise a Position value for Cloudflare
     * @param string $value Craft transform position
     * @param Asset $image
     * @return string Cloudflare transform position
     */
    public static function normalizePosition(string $value, Asset $image): string
    {
        $namedPositions = [
            "top-left" => [ 'x' => 0,   'y' => 0],
            "top-center" => [ 'x' => 0.5, 'y' => 0],
            "top-right" => [ 'x' => 1.0, 'y' => 0],
            "center-left" => [ 'x' => 0,   'y' => 0.5],
            "center-center" => [ 'x' => 0.5, 'y' => 0.5],
            "center-right" => [ 'x' => 1.0, 'y' => 0.5],
            "bottom-left" => [ 'x' => 0,   'y' => 1.0],
            "bottom-center" => [ 'x' => 0.5, 'y' => 1.0],
            "bottom-right" => [ 'x' => 1.0, 'y' => 1.0],
        ];

        if ($image->hasFocalPoint) {
            $result = self::positionToGravity($image->getFocalPoint());
        } elseif ($named = $namedPositions[$value] ?? false) {
            $result = self::positionToGravity($named);
        } else {
            $result = self::positionToGravity($namedPositions['center-center']);
        }

        return $result;
    }

    /**
     * @inerhitdoc
     */
    public static function buildUrl(Asset $image, string $baseUrl, array $transformParams = []): string
    {
        $folder = '';
        if (property_exists($image->fs, 'subfolder')) {
            $folder = App::parseEnv($image->fs->subfolder);
        }
        $key = ltrim($folder . $image->path, '/');

        $path = self::collapseSlashes(
            join( '/', [ self::encodeParams($transformParams), $key ])
        );

        return self::getBaseUrl($baseUrl) . $path;
    }

    /**
     * @inheritDoc
     */
    public static function getBaseUrl(string $baseUrl = '/'): string
    {
        return rtrim($baseUrl, '/') . self::CF_PATH_PREFIX;
    }

    /**
     * Encode the URL params according to the CF image resizing format
     * @param  array  $params param/value pairs
     * @return string
     * @see https://developers.cloudflare.com/images/image-resizing/url-format/
     */
    protected static function encodeParams(array $params): string
    {
        return join(
            ',',
            array_map(
                fn ($key) => "$key=$params[$key]",
                array_keys($params),
            )
        );
    }

    /**
     * Convert a Craft position assoc array to a Cloudflare gravity
     * @param  array  $position [ x => float , y => float]
     * @return string
     * @see https://developers.cloudflare.com/images/image-resizing/url-format/#gravity
     */
    protected static function positionToGravity(array $position): string
    {
        return $position['x'] . 'x' . $position['y'];
    }
}
