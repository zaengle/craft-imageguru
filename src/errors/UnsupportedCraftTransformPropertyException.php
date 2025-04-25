<?php

declare(strict_types=1);
/**
 * Image Guru plugin for Craft CMS 5.x
 *
 *
 * @link      https://zaengle.com
 * @copyright Copyright (c) 2022 Zaengle Corp
 */

namespace zaengle\imageguru\errors;

use yii\base\Exception;

/**
 * @author    Zaengle Corp
 * @package   Zaengle
 * @since     1.0.0
 */
class UnsupportedCraftTransformPropertyException extends Exception
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Unsupported Craft Transform property supplied';
    }
}
