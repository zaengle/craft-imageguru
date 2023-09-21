# Configuring the plugin

ImageGuru is entirely configured via its config file, a starter version of which will be created at `config/imageguru.php` as part of the plugin install command.

Out of the box, ImageGuru makes no changes to your image transforms until you configure it.

## Getting started

The config file expects a minimum of two keys:

- `enabledTransformers` - an array of transformers to register with Craft, supplied as escaped, namespaced classnames. Transformers must implement `craft\base\imagetransforms\ImageTransformerInterface`
- `volumes` - an array of volumes to configure transformers for, where the key is the volume handle, and the value is an array of config options for that volume that will be used to construct a `VolumeTransformSettings` model (see below). The special `*` key can be used to provide a default transformer for all volumes that do not have a specific transformer set. 

Example:

```php
return [
  '*' => [
    // Available transformers that should be registered with Craft
    'enabledTransformers' => [
      '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
    ],
    'volumes' => [
       // use `*` to provide a default / fallback transformer for any volume that does not have a specific transformer set 
      '*' => [
        'transformer' => '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
      ],
    ],
  ],
];
```

## `VolumeTransformSettings` - passing configuration to transformers

`VolumeTransformSettings` model supports the following properties:

- `string transformer` - the escaped, namespaced classname of the transformer to use for this volume, and which must match an `enabledTransformer`.
- `string transformBaseUrl` - the base URL to use for transforms (e.g. CDN hostname), defaults to `/
- `?string urlSigningSecret` - a secret key to use for signing transform URLs, if required by the transformer. If not set, no signing will be applied.
- `?array defaultParams` - an array of default parameters to apply to all transforms for this volume. These will be merged with any params passed to the `getTransformUrl` method, but can be overriden on a per-transform basis.
- `?array enforceParams` - an array of parameters that will be enforced on all transforms for this volume. These will be merged with any params passed to the `transformUrl` method and will override any params passed to the transformer's `getTransformUrl()` method.

Example:

```php
// config/imageguru.php
return [
  '*' => [
    'enabledTransformers' => [ '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer', ],
    'volumes' => [
      '*' => [
        'transformer' => '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
        'transformBaseUrl' => getenv('TRANSFORM_BASE_URL'),
        'defaultParams' => [
          'quality' => 75,
        ],     
        'enforceParams' => [
          'format' => 'auto',
          'metadata' => 'copyright',
        ],
      ],
    ],
  ],
];
```

## Multi environment config

The config file is environment-aware, so you can optionally specify a different transformer per environment. For example, you could use the native image transformer in dev, and a cloudflare transformer in production:

```php
// config/imageguru.php
return [
  'dev' => [
     'volumes' => [
       '*' => [
         'transformer' => '\\craft\\models\\ImageTransform',
       ],
     ],
  ],
  'production' => [
     'volumes' => [
       '*' => [
         'transformer' => '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
       ],
     ],
  ],
];
```
