<?php
/**
 * Image Guru plugin for Craft CMS 4.x
 *
 * Streamline your Image Transforms
 *
 * @link      https://zaengle.com
 * @copyright Copyright (c) 2022 Zaengle Corp
 */
return [
  '*' => [
    /**
     * Available transformers that should be registered with Craft
     */
    'enabledTransformers' => [
      // Supply an escaped namespaced classname to use,
      // Transformers must implement craft\base\imagetransforms\ImageTransformerInterface
      '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
    ],
    /**
     * Configure transformers per volume, or provide a global default
     */
    'volumes' => [
      /**
       * use `*` to provide a default / fallback transformer for any volume that does not 
       * have a specific transformer set 
       */
      '*' => [
        // Supply an escaped namespaced classname to use,
        // Transformers must implement craft\base\imagetransforms\ImageTransformerInterface
        'transformer' => '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
        // Supply the base URL to use for transforms (e.g. CDN hostname), defaults to `/`
        'transformBaseUrl' => getenv('TRANSFORM_BASE_URL'),
        // `defaultParams` will be applied to *every* transform without needing to be set in your template / Craft transform, but can be overriden by transform settings if matching kets supplied
        // @see https://developers.cloudflare.com/images/image-resizing/url-format/ for more opts
        'defaultParams' => [
          // 'quality' => 75,
          // 'gravity' => 'auto',
        ],

        // `enforceParams` will be applied to *every* transform, and cannot be overriden per transform,
        // because they do not exist in craft\models\ImageTransform
        // @see https://developers.cloudflare.com/images/image-resizing/url-format/
        'enforceParams' => [
          // 'format' => 'auto',
          // 'gravity' => 'auto',
          // 'metadata' => 'copyright',
        ],
      ],
      /**
       * Specify a transformer for a named volume
       */
      // 'myVolume' => [
      //   'transformer' => '\\my\\namespace\\transformers\\CustomTransformer',
      //   // Supply the base URL to use for transforms (e.g. CDN hostname), defaults to `/`
      //   'transformBaseUrl' => getenv('ANOTHER_TRANSFORM_BASE_URL'),
      // ],
      
      /**
       * To use a single transformer for *all* volumes, just set the config keys directly here
       */
      // 'transformer' => '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
      // 'transformBaseUrl' => getenv('TRANSFORM_BASE_URL'),
    ],
  ],
  /**
   * This is a multienvironment config, so you can optionally specify a different transformer per environment
   * 
   * e.g just use the Craft default transformer for local dev
   */ 
  // 'dev' => [
  //   'volumes' => [
  //     '*' => [
  //       'transformer' => '\\craft\\models\\ImageTransform',
  //     ],
  //   ],
  // ],
];
