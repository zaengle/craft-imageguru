<?php

declare(strict_types=1);
/**
 * Image Guru plugin for Craft CMS 4.x
 *
 * @link      https://zaengle.com
 * @copyright Copyright (c) 2022 Zaengle Corp
 */

namespace zaengle\imageguru\models;

use craft\base\Model;

/**
 * @author    Zaengle Corp
 * @package   Zaengle
 * @since     1.0.0
 */
class VolumeTransformSettings extends Model
{
    // Public Properties
    // =========================================================================

    public string $transformer;
    public string $transformBaseUrl = '/';
    public ?string $urlSigningSecret = null;
    public array $defaultParams = [];
    public array $enforceParams = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['transformer'], 'required'],
        ];
    }

    public function getShouldSignUrls(): bool
    {
        return (bool) ($this->urlSigningSecret ?? false);
    }
}
