<?php

declare(strict_types=1);
/**
 * Image Guru plugin for Craft CMS 4.x
 *
 * There's no place like home. There's no place like home. There's no place like home...
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
class NotS3AssetException extends Exception
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Asset is not in an AWS S3 filesystem';
    }
}
