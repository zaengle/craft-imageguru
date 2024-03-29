<?php

declare(strict_types=1);
/**
 * Image Guru plugin for Craft CMS 4.x
 *
 * @link      https://zaengle.com
 * @copyright Copyright (c) 2022 Zaengle Corp
 */

namespace zaengle\imageguru\models;

use craft\models\ImageTransform;

/**
 * @author    Zaengle Corp
 * @package   Zaengle
 * @since     1.0.0
 */
class CloudflareImageTransform extends ImageTransform
{
    // Public Properties
    // =========================================================================

    /**
     * @var array
     */
    public $enabledTransformers = [];
    /**
     * @var array
     */
    public $volumes = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['enabledTransformers', []],
            ['volumes', []],
        ];
    }
}
