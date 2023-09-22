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

namespace zaengle\imageguru;

use Craft;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\events\GenerateTransformEvent;

use craft\events\RegisterComponentTypesEvent;
use craft\services\ImageTransforms as CraftImageTransformsService;

use yii\base\Event;
use zaengle\imageguru\models\Settings as SettingsModel;


use zaengle\imageguru\services\Transformer as ImageGuruTransformerService;

/**
 * Class ImageGuru
 *
 * @author    Zaengle Corp
 * @package   ImageGuru
 * @since     1.0.0
 *
 * @property  ImageGuruTransformerService $transformer
 */
class ImageGuru extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var ImageGuru
     */
    public static Plugin $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * @var bool
     */
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'transformer' => ImageGuruTransformerService::class,
        ]);

        $this->installEventHandlers();

        Craft::info('image-guru plugin loaded');
    }

    // Protected Methods
    // =========================================================================
    protected function installEventHandlers(): void
    {
        // Register the transformer(s) with Craft
        Event::on(
            CraftImageTransformsService::class,
            CraftImageTransformsService::EVENT_REGISTER_IMAGE_TRANSFORMERS,
            function(RegisterComponentTypesEvent $event) {
                Craft::debug('ImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS', __METHOD__);

                /**
                 * @var SettingsModel $settings
                 */
                $settings = $this->getSettings();
                foreach ($settings->enabledTransformers as $transformerClass) {
                    $event->types[] = $transformerClass;
                }
            }
        );

        // Use the selected transformer
        Event::on(
            Asset::class,
            Asset::EVENT_BEFORE_GENERATE_TRANSFORM,
            function(GenerateTransformEvent $event) {
                Craft::debug('Asset::EVENT_BEFORE_GENERATE_TRANSFORM', __METHOD__);
                $transformerSettings = $this->transformer->getTransformerSettingsByAsset($event->asset);

                if ($transformerSettings->transformer::supports($event->asset->extension)) {
                    $event->transform->setTransformer($transformerSettings->transformer);
                }
            }
        );
    }

    /**
     * Copy example config to project's config folder
     */
    protected function afterInstall(): void
    {
        $configSource = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.example.php';
        $configTarget = \Craft::$app->getConfig()->configDir . DIRECTORY_SEPARATOR . 'imageguru.php';

        if (!file_exists($configTarget)) {
            copy($configSource, $configTarget);
        }
    }

    protected function createSettingsModel(): SettingsModel
    {
        return new SettingsModel();
    }
}
