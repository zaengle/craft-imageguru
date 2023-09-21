# Bundled Transformers

Currently, ImageGuru Bundles transformers for three image transform services:

1. [AWS Serverless Sharp Image Handler](#_1-aws-serverless-sharp-image-handler)
2. [Cloudflare Image Resizing (Basic)](#_2-cloudflare-image-resizing-basic)
3. [Cloudflare Image Resizing (Worker)](#_3-cloudflare-image-resizing-worker)


## 1. AWS Serverless Sharp Image Handler

The AWS Serverless Sharp Image Handler is a pre-rolled image transform service that uses AWS Lambda and CloudFront to provide a performant, scalable image transform service. It is a great option if you are already using AWS and want to keep your infrastructure costs down. You can deploy a new instance of the service in minutes using the [AWS Solutions Library](https://aws.amazon.com/solutions/implementations/serverless-image-handler/).

You have a handler running, you can configure ImageGuru to use it by adding the following to your `config/imageguru.php` file:

```php
use craft\helpers\App;
return [
  '*' => [
    'enabledTransformers' => [
      // Use the bundled Transformer, or extend it if you need to
      '\\zaengle\\imageguru\\transformers\\AwsServerlessSharpTransformer',
    ],
    'volumes' => [
        'transformer' => '\\zaengle\\imageguru\\transformers\\AwsServerlessSharpTransformer',
        // Supply the base URL to use for transforms (e.g. CDN hostname), defaults to `/`
        'transformBaseUrl' => App::env('AWS_SERVERLESS_IMAGE_HANDLER_ENDPOINT'),
        // Supply a secret key to use for signing transform URLs, if required by the deployed handler
        'urlSigningSecret' => App::env('AWS_SERVERLESS_IMAGE_HANDLER_SECRET'),
    ],
  ],
];
```

### AWS Serverless Sharp Image Handler Quirks

- The Sharp image manipulation library, which The AWS Serverless Sharp Image Handler uses behind the scenes, [does not support float values for the `position` parameter](https://sharp.pixelplumbing.com/api-resize#resize). Instead, it only supports named positions like `top-left` or `center-right`. To provide maximum compatability out of the box with Craft's native transforms, when passed a float value, the transformer will round the value to the nearest named position. See `src/transformers/AwsServerlessSharpTransformer::getPosition()` for details.
- The AWS Serverless Sharp Image Handler passes transform parameters using a base64 encoded JSON object in the URL path, which can make transforms hard to debug. Calling `atob()` on the path segment of the URL from your browser's JS console will decode the JSON object.

## 2. Cloudflare Image Resizing (Basic)

Cloudflare Image Resizing is an image transform service from Cloudflare on their Business & Enterprise plans. It is a great option if you are already using Cloudflare and want to keep your infrastructure costs down. You can [enable it in your Cloudflare dashboard in seconds](https://developers.cloudflare.com/images/image-resizing/enable-image-resizing). For it to work, your source images must be served from a hostname that is proxied by Cloudflare.

Once you've enabled Cloudflare Image Resizing, you can configure ImageGuru to use it by adding the following to your `config/imageguru.php` file:

```php
return [
  'enabledTransformers' => [
    '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
  ],
  'volumes' => [
    '*' => [
      // Supply an escaped namespaced classname to use,
      // Transformers must implement craft\base\imagetransforms\ImageTransformerInterface
      'transformer' => '\\zaengle\\imageguru\\transformers\\CloudflareBasicTransformer',
      // Supply the base URL to use for transforms (e.g. CDN hostname), defaults to `/`
      'transformBaseUrl' => getenv('TRANSFORM_BASE_URL'),
    ],
  ],
];  

```

### Cloudflare Image Resizing (Basic) Quirks

There are a few things that the Cloudflare Image Resizing transformer does not support:

- Does not support every parameter that Craft's native transforms do. See the [Cloudflare Image Resizing docs](https://developers.cloudflare.com/images/image-resizing/url-format/) for details. ImageGuru intelligently maps parameter names from Craft transforms to values in the Cloudflare Image Resizing URL format, but there are some differences. See `src/transformers/CloudflareBasicTransformer` for details.
- Does not support signed/hashed URLs. If you need this, you may be able to use the Cloudflare Image Resizing (Worker) transformer below instead.
- Like the AWS Serverless Sharp handler, it [does not support float values for the `position` parameter (called `gravity` in CF's docs)](https://developers.cloudflare.com/images/image-resizing/url-format/#gravity). Instead, it only supports named positions like `top-left` or `center-right`. To provide maximum compatability out of the box with Craft's native transforms, when passed a float value, the transformer will round the value to the nearest named position. See `src/transformers/CloudflareBasicTransformer::getPosition()` for details.

## 3. Cloudflare Image Resizing (Worker)

Using [Cloudflare Image Resizing via a worker](https://developers.cloudflare.com/images/image-resizing/resize-with-workers/), gives much more control over the image request. ImageGuru supports this via the `CloudflareWorkerTransformer` transformer, and its included example worker script. 

The worker script can be found in the plugin's source in your `vendor` directory at `vendor/zaengle/craft-imageguru/cloudflare` and includes a README.md with full instructions about how to deploy the worker. You can copy this directory to your project and customize it to your needs. 

Out of the box the worker integration supports:

- Signed URLs
- Custom header pass-through
- Float values for `position`, giving full compatability with Craft's native transforms

Note that for image resizing to work, your worker must be deployed to a route under a domain that has the Cloudflare Images paid add-on enabled. It will not work when accessed via a default `workers.dev` subdomain. The worker does this by default, using a `/resize` route, but you can customize this if you need to.

Most of the configuration for the worker is done via environment variables. See the `README` file in `vendor/zaengle/craft-imageguru/cloudflare` for full details.