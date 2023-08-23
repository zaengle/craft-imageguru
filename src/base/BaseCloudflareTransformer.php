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

namespace zaengle\imageguru\base;

use craft\base\Component;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\elements\Asset;
use craft\models\ImageTransform as CraftImageTransform;

use zaengle\imageguru\models\CloudflareImageTransform;
use zaengle\imageguru\models\VolumeTransformSettings;

/**
 * Base class for transformers that use  Cloudflare's Image Resizing service
 *
 * @author Zaengle Corp. <hello@zaengle.com>
 * @since 1.0.0
 * @see https://developers.cloudflare.com/images/image-resizing/ CF service documentation
 */
abstract class BaseCloudflareTransformer extends Component implements ImageTransformerInterface
{
    /**
     * The image formats this transformer supports (doesn't support SVG)
     */
    public const SUPPORTED_EXTENSIONS_PATTERN = '/(jp(e?)g|png|gif|webp|avif)/';

    /**
     * The property names from craft\models\ImageTransform to use when building
     * transform URLS
     */
    public const CRAFT_TRANSFORM_PARAMS = [
        'height',
        'interlace',
        'mode',
        'position',
        'quality',
        'width',
    ];

    /**
     * The property names from zaengle\imageguru\models\CloudflareImageTransform to
     * use when building transform URLS
     */
    public const CLOUDFLARE_TRANSFORM_PARAMS = [
        'anim',
        'background',
        'blur',
        'brightness',
        'contrast',
        'dpr',
        'fit',
        'format',
        'gamma',
        'gravity',
        'height',
        'metadata',
        'onerror',
        'quality',
        'rotate',
        'sharpen',
        'trim',
        'width',
    ];

    public const PARAM_MODE = 'mode';
    public const PARAM_POSITION = 'position';
    public const MODE_CROP = 'crop';

    /**
     * @inheritdoc
     *
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        // @todo clear the CF cache for this path
    }

    // Static Methods
    // =========================================================================

    /**
     * Normalize a param name
     * @param  string $key
     * @return string
     */
    public static function getNormalizedParamName(string $key): string
    {
        $TRANSLATE_PARAMS = [
            'mode' => 'fit',
            'position' => 'gravity',
        ];

        return in_array($key, array_keys($TRANSLATE_PARAMS)) ? $TRANSLATE_PARAMS[$key] : $key;
    }

    /**
     * Normalize a param value
     * @param  string $paramName name of transform property
     * @param  string $value value of transform property
     * @param  Asset $image
     * @return mixed param value
     */
    public static function getNormalizedParamValue(string $paramName, mixed $value, Asset $image): mixed
    {
        return match ($paramName) {
            self::PARAM_MODE => self::normalizeMode($value),
            self::PARAM_POSITION => self::normalizePosition($value, $image),
            default => $value,
        };
    }

    /**
     * Normalise a Mode value for Cloudflare
     * @param  string $value Craft transform mode
     * @return string        Cloudflare transform mode
     */
    public static function normalizeMode(string $value): string
    {
        return match ($value) {
            'fit' => 'contain',
            // CF never changes the aspect ratio of the image, so 'cover' is the closest
            // we can get
            // @todo throw or warn when this is used in dev mode?
            'stretch', self::MODE_CROP => 'cover',
            default => $value,
        };
    }

    /**
     * Normalise a Position value for Cloudflare
     * @param string $value Craft transform position
     * @param Asset $image
     * @return string Cloudflare transform position
     */
    public static function normalizePosition(string $value, Asset $image): string
    {
        return $value;
    }

    /**
     * Check if the transformer supports a file extension
     *
     * @todo checking mimetype would be much better here
     *
     * @param  string $extension
     * @return bool
     */
    public static function supports(string $extension): bool
    {
        return (bool) preg_match(self::SUPPORTED_EXTENSIONS_PATTERN, $extension);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Combine the params from the transform with the defaults + enforced params from the plugin settings
     * for this asset's volume
     * @param CloudflareImageTransform|CraftImageTransform $transform
     * @param Asset $image
     * @param VolumeTransformSettings $volumeSettings
     * @return array associative array of param / value pairs
     */
    public static function mergeParams(CloudflareImageTransform|CraftImageTransform $transform, Asset $image, VolumeTransformSettings $volumeSettings): array
    {
        return array_merge(
            $volumeSettings->defaultParams,
            self::normalizeParams($transform, $image),
            $volumeSettings->enforceParams
        );
    }

    /**
     * Normalize / map the param names + values to work with CF
     * @param CraftImageTransform|CloudflareImageTransform $transform
     * @param Asset $image
     * @return array normalized param / value pairs
     */
    public static function normalizeParams(CraftImageTransform|CloudflareImageTransform $transform, Asset $image): array
    {
        $params = is_a($transform, CloudflareImageTransform::class)
                ? self::CLOUDFLARE_TRANSFORM_PARAMS
                : self::CRAFT_TRANSFORM_PARAMS;

        $result = [];

        foreach ($params as $paramName) {
            if ($transform->$paramName ?? false) {
                $normalizedValue = self::getNormalizedParamValue($paramName, $transform->$paramName, $image);
                $normalizedKey = self::getNormalizedParamName($paramName);

                if (in_array($normalizedKey, self::CLOUDFLARE_TRANSFORM_PARAMS)) {
                    $result[$normalizedKey] = $normalizedValue;
                }
            }
        }

        return $result;
    }

    /**
     * Assemble the URL form the transform
     * @param  Asset  $image
     * @param  string $baseUrl base url + path excluding the CF /cdn-cgi/image/ segment
     * @param  array  $transformParams normalized param / value pairs
     * @return string The completed transform URL
     */
    public abstract static function buildUrl(Asset $image, string $baseUrl, array $transformParams = []): string;


    /**
     * Adds the CF image resizing segment to the base url
     * @param  string $baseUrl
     * @return string
     */
    public static function getBaseUrl(string $baseUrl = '/'): string
    {
        return rtrim($baseUrl, '/') . '/';
    }


    /**
     * Ensure no repeated slashes
     * @param  string $str
     * @return string
     */
    public static function collapseSlashes(string $str): string
    {
        return ltrim(preg_replace('#/{2,}#', '/', $str), '/');
    }
}
