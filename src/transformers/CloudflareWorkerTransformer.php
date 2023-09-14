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
 * CloudflareBasicTransformer transforms image assets using Cloudflare's Image Resizing service via Cloudflare Workers
 *
 * @author Zaengle Corp. <hello@zaengle.com>
 * @since 1.1
 *
 * @see https://developers.cloudflare.com/images/image-resizing/resize-with-workers
 */
class CloudflareWorkerTransformer extends BaseCloudflareTransformer implements ImageTransformerInterface
{
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

        $params = $this->mergeParams($imageTransform, $asset, $volumeSettings);

        if ($volumeSettings->getShouldSignUrls()) {
            $params['verify'] = hash_hmac(
                'sha256',
                $this->buildUrl($asset, '', $params),
                $volumeSettings->urlSigningSecret,
            );
        }

        return $this->buildUrl($asset, $volumeSettings->transformBaseUrl, $params);
    }

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
     * Combine the params from the transform with the defaults + enforced params from the plugin settings
     * for this asset's volume
     * @param CloudflareImageTransform|CraftImageTransform $transform
     * @param Asset $image
     * @param VolumeTransformSettings $volumeSettings
     * @return array associative array of param / value pairs
     */
    public function mergeParams(CloudflareImageTransform|CraftImageTransform $transform, Asset $image, VolumeTransformSettings $volumeSettings): array
    {
        $clonedTransform = clone $transform;
        $clonedTransform->position = '';

        return array_merge(
            $volumeSettings->defaultParams,
            $this->normalizePosition($transform->position, $image),
            $this->normalizeParams($clonedTransform , $image),
            $volumeSettings->enforceParams
        );
    }

    /**
     * Assemble the URL form the transform
     * @param  Asset  $image
     * @param  string $baseUrl base url + path excluding the CF /cdn-cgi/image/ segment
     * @param  array  $transformParams normalized param / value pairs
     * @return string The completed transform URL
     */
    public function buildUrl(Asset $image, string $baseUrl, array $transformParams = []): string
    {
        $folder = '';
        if (property_exists($image->fs, 'subfolder')) {
            $folder = App::parseEnv($image->fs->subfolder);
        }
        $path = ltrim($folder . $image->path, '/');



        return rtrim($baseUrl, '/'). '/' . $path . '?' . http_build_query($transformParams);
    }

    /**
     * @inheritDoc
     */
    protected function positionToGravity(array $position): mixed
    {
        return [
            'fp-x' => $position['x'],
            'fp-y' => $position['y'],
        ];
    }
}
