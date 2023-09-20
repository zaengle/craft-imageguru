# Cloudflare Worker for image transform via Cloudflare Images

This worker is used to resize images on the fly. It is used in conjunction with the [Cloudflare Image Resizing](https://developers.cloudflare.com/images/image-resizing/) service and is intended for use with the [Image Guru](https://github.com/zaengle/craft-imageguru/) Craft plugin (though it does not require it).

**Note that this worker _must_ be deployed to a Cloudflare account and zone that has the Cloudflare Images paid add-on enabled in order to work. It also needs to be deployed to a route under that domain, it will not work when accessed via a default `workers.dev` subdomain **


## Configuration

The worker is configured using environment variables. These can be set in the [Wrangler configuration file](https://developers.cloudflare.com/workers/wrangler/configuration/) ([`wrangler.toml`](./wrangler.toml)) or by setting them in the Cloudflare dashboard.

| Variable                        | Description                                                                                                                                                | Example                                |
|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------|
| `ORIGIN: string`                | The origin URL to fetch images from<br/>This is probably either your webserver or a public cloud storage bucket                                            | `https://example.com`                  |
| `VERIFY_REQUESTS: boolean`      | A flag for whether or not the worker should verify request signatures (see below)                                                                          | `true`, `false`                        |
| `SHARED_SIGNING_SECRET: string` | A shared key that is used to sign requests with a [HMAC](https://en.wikipedia.org/wiki/HMAC) in your application and verify those signatures in the worker | `19f507c7-75b3-47fd-8ad6-85c92658396b` |
| `ROUTE: string`                 | The route to deploy the worker on (must be under your domain that has the CF images paid add-on enabled)                                                   | `/resize`                              |

## Deployment

The worker needs to be deployed to Cloudflare using the [Wrangler](https://developers.cloudflare.com/workers/cli-wrangler) CLI. You will need to have a Cloudflare account and zone set up with the [Cloudflare Images](https://developers.cloudflare.com/images/) paid add-on enabled.

```shell
npm install
# install the wrangler CLI globally
npm install -g wrangler
# log in
wrangler login
# publish
npm run deploy
```

## Usage

Requests should use the following format: `https://<your-worker-url><path-to-image-in-origin>?<transformations>&verify=<signature>`

Where:

- `<your-worker-url>` from the Cloudflare dashboard / Wrangler CLI output
- `<path-to-image-in-origin>` the absolute path to the image in your origin (**must** start with a slash `/`)
- `<transformations>` a query string encoded list of transformations to apply to the image. All the transformations available in the Cloudflare [Resize with Cloudflare Workers](https://developers.cloudflare.com/images/image-resizing/resize-with-workers/) docs are supported except for [`border`](https://developers.cloudflare.com/images/image-resizing/resize-with-workers/#border) and [`trim`](https://developers.cloudflare.com/images/image-resizing/resize-with-workers/#trim). Additionally `fp-x` and `fp-y` are supported for [providing a `gravity` value as coordinates](https://developers.cloudflare.com/images/image-resizing/resize-with-workers/#gravity).
- `<signature>` If the `VERIFY_REQUESTS` environment variable is set to `true`,  a HMAC digest of the request path and query string must be generated and appended to the request URL. This is used to verify that the request is coming from your application and not from a malicious actor. Use the `SHARED_SIGNING_SECRET` environment variable as the signing key. The HMAC should use `sha-256` as the hashing algorithm and be appended to the URL as the value of the `verify` query parameter.

### Basic PHP example for signing requests

```php
function sign($pathWithQueryString) {
    $secret = getenv('CF_IMAGES_SIGNING_SECRET');
    $hash = hash_hmac('sha256', $pathWithQueryString, $secret);
    return $pathWithQueryString . '&verify=' . $hash;
}

sign('/path/to/image.jpg?width=100&height=100');
```

### Invalid requests

Invalid requests will return a `400 Bad Request` response, with details of the error in the response body.

### Non-raster image files

Only raster image file formats are supported. Calling the worker with a path to an SVG or non-image file with return an error

## Local Development

```shell
npm install
npm start
```

Note: image transforms will not work when running the worker locally - the origin image will just be returned untransformed. You will need to deploy the worker to a Cloudflare account and zone that has the Cloudflare Images paid add-on enabled in order to test image transforms end-to-end.

### Unit tests

We has them. Run them with `npm test`. Write them using [Vitest](https://vitest.dev/).

### Prettier

Is included. Run it with `npm run format`.

## Todo

- Abstract this to the ImageGuru repo & add a `CloudflareWorker` adapter
