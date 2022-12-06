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

namespace zaengle\imageguru\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\imagetransforms\ImageTransformer;
use craft\models\ImageTransform;
use craft\models\Volume;

use zaengle\imageguru\ImageGuru;
use zaengle\imageguru\models\Settings as SettingsModel;
use zaengle\imageguru\models\VolumeTransformSettings as VolumeTransformSettingsModel;

/**
 * @author    Zaengle Corp
 * @package   ImageGuru
 * @since     1.0.0
 * @property SettingsModel $settings
 */
class Transformer extends Component
{
    public const SHARED_SETTINGS_KEY = '*';

    public static SettingsModel $settings;

    public function init(): void
    {
        parent::init();

        /**
         * @var SettingsModel $settings
         */
        $settings = ImageGuru::getInstance()->getSettings();

        self::$settings = $settings;
    }

    // Public Methods
    // =========================================================================

    public function getTransformerSettingsByAsset(Asset $asset): VolumeTransformSettingsModel
    {
        return $this->getTransformerSettingsByAssetVolume($asset->volume);
    }

    /** @noinspection PhpMultipleClassDeclarationsInspection */
    public function getTransformerSettingsByAssetVolume(Volume $volume): VolumeTransformSettingsModel
    {
        $knownVolumes = self::$settings->volumes;

        $volumeSettings = $knownVolumes[$volume->handle]
                      ?? $knownVolumes[self::SHARED_SETTINGS_KEY]
                      ?? $knownVolumes;

        if (empty($volumeSettings)) {
            // fall back to native transformer if no config found
            Craft::warning('[image-guru] No Image Transformer settings found');

            $volumeSettings = [
        'transformer' => '\\craft\\models\\ImageTransform',
      ];
        }

        return new VolumeTransformSettingsModel($volumeSettings);
    }

    /**
     * Generate a transform for an image, without using the Craft Transform API
     *
     * Allows you to use the full range of params in the CF image resizing API
     *
     * @see https://developers.cloudflare.com/images/image-resizing/url-format/
     *
     * @todo incomplete
     * @param Asset|string $asset Image Asset or Image Path
     * @param array|ImageTransform $transform Transform definition array literal
     * @return string Transform URL
     */
    public function transform(Asset|string $asset, array|ImageTransform $transform): string
    {
        $transformer = $this->getTransformer($asset);

        return $transformer->getTransformUrl($asset, $transform, false);
    }

    /**
     * Get an instance of the transformer for this image (based on volume settings)
     * @param Asset $asset
     * @return ImageTransformer
     */
    protected function getTransformer(Asset $asset): ImageTransformer
    {
        $settings = $this->getTransformerSettingsByAsset($asset);

        return new $settings->transformer();
    }
}
