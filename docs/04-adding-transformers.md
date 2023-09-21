# Writing a Custom Transformer

If ImageGuru's bundled transformers don't meet your needs, you can easily write your own. Transformers must implement the `craft\base\imagetransforms\ImageTransformerInterface` interface, which requires the following methods:

| Method                                                                                                             | Description                             |
|--------------------------------------------------------------------------------------------------------------------|-----------------------------------------|
| `public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately = false): string` | Returns the URL for an image transform  |
| `public function invalidateAssetTransforms(Asset $asset): void`                                                    | Invalidates all transforms for an asset |

Additionally ImageGuru will pass a `VolumeTransformSettings` model to the transformer's constructor, which can be used to pass configuration to the transformer. See the [Configuring Transformers](./02-config#volumetransformsettings-passing-configuration-to-transformers) for details.

You can then use your transformer with ImageGuruin just the same way you'd use a bundled transformer, by adding it to the `enabledTransformers` config option in your `config/imageguru.php` file, and applying it to a volume in the `volumes` config option. 

```php
return [
  'enabledTransformers' => [
    '\\modules\\transformers\\CustomTransformer',
  ],
  'volumes' => [
    '*' => 
      'transformer' => '\\modules\\transformers\\CustomTransformer',
      'transformBaseUrl' => getenv('TRANSFORM_BASE_URL'),
    ],
  ],
];
```