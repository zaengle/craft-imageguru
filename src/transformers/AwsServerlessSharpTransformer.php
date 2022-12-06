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

use craft\awss3\Fs as AwsFs;
use craft\base\Component;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\elements\Asset;
use craft\errors\ImageTransformException;
use craft\helpers\App;
use craft\helpers\Image;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;
use craft\models\ImageTransform as CraftImageTransform;

use zaengle\imageguru\errors\NotS3AssetException;
use zaengle\imageguru\ImageGuru;
use zaengle\imageguru\models\VolumeTransformSettings;

/**
 * AwsServerlessImageHandlerTransformer transforms image assets using AWS's pre-rolled Serverless Image Handler
 *
 * @author Zaengle Corp. <hello@zaengle.com>
 * @since 1.1.0
 * @see https://aws.amazon.com/solutions/implementations/serverless-image-handler/ AWS service documentation
 */
class AwsServerlessSharpTransformer extends Component implements ImageTransformerInterface
{
    /**
     * Map Craft ImageTransform formats => Sharp formats
     */
    public const MAP_TRANSFORM_FORMATS = [
        'jpg' => 'jpeg',
    ];

    /**
     * Map Craft ImageTransform modes => Sharp resize.fit options
     */
    public const MAP_TRANSFORM_MODES = [
        'crop' => 'cover',
        'fit' => 'inside',
        'stretch' => 'fill',
    ];

    /**
     * Map Craft Image Transform attrs => Sharp resize options
     */
    public const MAP_TRANSFORM_RESIZE_ATTRIBUTES = [
        'width' => 'width',
        'height' => 'height',
        'mode' => 'fit',
    ];

    /**
     * The image formats this transformer supports (e.g. doesn't support SVG)
     */
    public const SUPPORTED_EXTENSIONS_PATTERN = '/(jp(e?)g|png|gif|webp)/';
    // Public Methods
    // =========================================================================

    /**
     * Returns the URL for an image transform
     *
     * @param Asset $asset
     * @param CraftImageTransform $imageTransform
     * @param bool $immediately Ignored/unused, declaration required for compatibility with default transformer
     * @return string The transform URL
     * @throws NotS3AssetException
     * @throws ImageTransformException
     */
    public function getTransformUrl(Asset $asset, CraftImageTransform $imageTransform, bool $immediately): string
    {
        $imageTransform = TransformHelper::normalizeTransform($imageTransform);
        /*
         * @var VolumeTransformSettings
         */
        $volumeSettings = ImageGuru::getInstance()->transformer->getTransformerSettingsByAsset($asset);

        if ($asset->fs instanceof AwsFs) {
            $bucket = App::parseEnv($asset->fs->bucket);
            $folder = App::parseEnv($asset->fs->subfolder);
            $key = ltrim($folder . $asset->path, '/');
        } else {
            throw new NotS3AssetException();
        }

        $edits = self::paramsToEdits($asset, $imageTransform/*, $volumeSettings*/);

        return self::buildUrl($bucket, $key, $edits, $volumeSettings);
    }

    /**
     * @inheritdoc
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        // @todo clear the CloudFront cache for this path
    }

    /**
     * Builds a signed AWS API gateway URL for set of transform options
     *
     * @param string $bucket S3 Bucket Name
     * @param string $key Path in the bucket
     * @param array $edits Sharp transform options
     * @param VolumeTransformSettings $settings
     * @return string URL to transformed image
     */
    public static function buildUrl(string $bucket, string $key, array $edits, VolumeTransformSettings $settings): string
    {
        $path = self::buildPath($bucket, $key, $edits);

        if ($settings->urlSigningSecret) {
            $path = self::signUrl($path, $settings->urlSigningSecret);
        }

        return $settings->transformBaseUrl . $path;
    }

    /**
     * @param string $bucket S3 Bucket Name
     * @param string $key Path in the bucket
     * @param array $edits Sharp transform options
     * @return string Base64 + JSON encoded URL Path
     */
    public static function buildPath(string $bucket, string $key, array $edits): string
    {
        return base64_encode(json_encode([
            'bucket' => $bucket,
            'key' => $key,
            'edits' => $edits,
        ], JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    }

    /**
     * Translates Craft Image Transform attrs => Sharp options hash
     * @param Asset $image
     * @param ImageTransform $transform
     * @return array
     */
    public static function paramsToEdits(Asset $image, CraftImageTransform $transform/*, VolumeTransformSettings $settings*/): array
    {
        $edits = [];

        $format = self::getFormatName($image, $transform);
        $edits[$format] = self::getFormatValues($format, $transform);

        foreach (self::MAP_TRANSFORM_RESIZE_ATTRIBUTES as $key => $value) {
            if (! empty($transform[$key])) {
                $edits['resize'][$value] = $transform[$key];
            }
        }

        if ($position = self::getPosition($transform->position, $image->getFocalPoint())) {
            $edits['resize']['position'] = $position;
        }

        $mode = strtolower($edits['resize']['fit'] ?? 'cover');
        $edits['resize']['fit'] = self::MAP_TRANSFORM_MODES[$mode] ?? $mode;

        return $edits;
    }

    /**
     * Append a signed signature to a URL path
     * @param string $path URL path with Sharp options
     * @param string $key Shared signing secret from AWS config
     * @return string
     */
    public static function signUrl(string $path, string $key): string
    {
        $path = str_starts_with($path, '/') ? $path : '/' . $path;

        return UrlHelper::urlWithParams($path, [
            'signature' => hash_hmac('sha256', $path, $key),
        ]);
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

    /**
     * Derive the file format name from the Asset + Transform combo
     *
     * @param Asset $asset
     * @param ImageTransform $transform
     * @return string
     */
    protected static function getFormatName(Asset $asset, ImageTransform $transform): string
    {
        $format = $transform->format;

        // if not explicitly set, derive the format from the asset
        if (empty($format)) {
            if (in_array(mb_strtolower($asset->getExtension()), Image::webSafeFormats(), true)) {
                $format = $asset->getExtension();
            } else {
                $format = 'webp';
            }
        }

        $format = strtolower($format);

        return self::MAP_TRANSFORM_FORMATS[$format] ?? $format;
    }

    /**
     * Generate Sharp file-format specific options
     *
     * @param string $format
     * @param ImageTransform $transform
     * @return array
     */
    protected static function getFormatValues(string $format, ImageTransform $transform): array
    {
        $result = [
            'quality' => $transform->quality ?? 80,
        ];
        // Format-specific settings
        switch ($format) {
            case 'webp':
                $result['nearLossless'] = true;

                break;

            case 'jpeg':
                $result['progressive'] = $transform->interlace !== 'none';

                if ($result['progressive']) {
                    $result['optimizeScans'] = true;
                }
                $result['trellisQuantisation'] = true;
                $result['overshootDeringing'] = true;

                break;
            case 'png':
                $result['progressive'] = $transform->interlace !== 'none';

                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * Translate Craft ImageTransform position values to Sharp position option
     * @param string|null $position
     * @param array|null $focalPoint
     * @return string|null
     */
    public static function getPosition(?string $position, ?array $focalPoint): ?string
    {
        if (! empty($focalPoint)) {
            $x = match (true) {
                $focalPoint['x'] < 0.33 => 'left',
                $focalPoint['x'] > 0.67 => 'right',
                default => 'center',
            };
            $y = match (true) {
                $focalPoint['y'] < 0.33 => 'top',
                $focalPoint['y'] > 0.67 => 'bottom',
                default => 'center',
            };
            $position = $x.'-'.$y;
        }
        if (! empty($position)) {
            if (preg_match('/(left|center|right)-(top|center|bottom)/', $position)) {
                $positions = explode('-', $position);
                $positions = array_diff($positions, ['center']);
                if (! empty($positions) && $position !== 'center-center') {
                    return implode(' ', $positions);
                }
            }
        }

        return null;
    }
}
