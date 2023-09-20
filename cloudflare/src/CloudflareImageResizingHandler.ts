import type { Request, RequestInitCfPropertiesImage, Response } from '@cloudflare/workers-types'
import type Env from './types/Env'

import StringHelper from './util/StringHelper'
import BufferHelper from './util/BufferHelper'
import { toBoolean } from './util/BooleanHelper'

/**
 * Handle image resizing requests
 */
export default class CloudFlareImageResizingHandler {
  $signingSecret: string
  $verifyRequestSignatures: boolean = true
  $origin: string
  $route: string
  $verifyParam: string = 'verify'
  // trim and border not supported
  TRANSFORM_DEFAULTS: RequestInitCfPropertiesImage = {
    anim: false,
    background: null,
    blur: null,
    brightness: null,
    compression: null,
    contrast: null,
    dpr: null,
    fit: 'crop',
    format: null,
    gamma: null,
    gravity: 'auto',
    height: null,
    quality: 80,
    rotate: null,
    sharpen: null,
    width: null,
  }

  constructor(env: Env) {
    this.$origin = env.ORIGIN
    this.$route = env.ROUTE
    this.$verifyRequestSignatures = env.VERIFY_REQUESTS ?? true
    this.$signingSecret = env.SHARED_SIGNING_SECRET
  }

  /**
   * Respond to an image resizing request
   *
   * - Request URL Pathname is used to build an origin image URL
   * - URL params used to build a Cloudflare Image Resizing transform object.
   *
   * @param request
   */
  async process(request: Request) {
    const url = new URL(request.url)

    url.pathname = url.pathname.replace(this.startsWithPattern(this.$route), '')

    const { headers } = request
    const { searchParams, pathname } = url

    try {
      const verified = await this.verifySignature(url)

      if (!verified) {
        return new Response('Invalid URL signature', { status: 400 })
      }
      const originImageUrl = this.getValidOriginImageUrl(pathname)
      const transform = this.getTransform(searchParams, headers)

      console.log(transform)

      return fetch(
        // Returning fetch() with resizing options will pass through response with the resized image.
        originImageUrl,
        {
          headers,
          // Cloudflare-specific options go in the cf object.
          cf: { image: transform },

        },
      )
    } catch ($e) {
      return new Response($e.message, { status: 400 })
    }
  }

  /**
   * Check the path is valid and build an origin image URL
   *
   * @param path
   * @throws Error
   * @return URL
   */
  getValidOriginImageUrl(path: string): URL {
    if (!path) {
      throw new Error('A path is required')
    }
    // Must be a valid image URL with valid extension
    const originImageUrl = new URL(`${StringHelper.trimSlashes(this.$origin)}/${StringHelper.trimSlashes(path)}`)

    // Only allow URLs with JPEG, PNG, GIF, or WebP file extensions
    // @see https://developers.cloudflare.com/images/url-format#supported-formats-and-limitations
    if (!/\.(jpe?g|png|gif|webp|avif)$/i.test(originImageUrl.pathname)) {
      throw new Error('Not an allowed file extension')
    }
    return originImageUrl
  }

  /**
   * Build a Cloudflare Image Resizing transform object
   *
   * @see RequestInitCfPropertiesImage
   * @see https://developers.cloudflare.com/images/image-resizing/resize-with-workers
   * @param searchParams
   * @param headers
   * @return RequestInitCfPropertiesImage
   */
  getTransform(searchParams: URLSearchParams, headers: Headers): RequestInitCfPropertiesImage {
    // Copy permitted parameters from query string to transform options.
    const transform = Object.keys(this.TRANSFORM_DEFAULTS).reduce((result, key) => {
      const value = this.coerceUrlParameter(key, searchParams.get(key))

      if (value) {
        result[key] = value
      }
      return result
    }, this.TRANSFORM_DEFAULTS)

    // Handle fp-x and fp-y custom parameters for focal point cropping
    if (searchParams.get('fp-x') && searchParams.get('fp-y')) {
      if (transform?.gravity !== 'auto') {
        throw new Error('Cannot use fp-x and fp-y with gravity set to a value other than auto')
      }
      transform.gravity = {
        x: parseFloat(searchParams.get('fp-x') as string),
        y: parseFloat(searchParams.get('fp-y') as string),
      }
    }

    // Do add any additional validations here, e.g. max image size

    // Format negotiation. Check the Accept header unless we're forcing a format
    const accept = headers.get('Accept')
    if (transform?.format) {
      if (/image\/avif/.test(accept)) {
        transform.format = 'avif'
      } else if (/image\/webp/.test(accept)) {
        transform.format = 'webp'
      }
    }

    // Clean up empty keys and return
    return Object.entries(transform).reduce(
      (result, [key, value]): RequestInitCfPropertiesImage => (value !== null ? { ...result, [key]: value } : result),
      {} as RequestInitCfPropertiesImage,
    )
  }

  coerceUrlParameter(key: string, value: string): string | number | boolean | object | null {
    switch (key) {
      // Integer
      case 'height':
      case 'quality':
      case 'rotate':
      case 'width':
        return parseInt(value, 10)
      // Float
      case 'blur':
      case 'brightness':
      case 'contrast':
      case 'dpr':
      case 'gamma':
      case 'sharpen':
      case 'fp-x':
      case 'fp-y':
        return parseFloat(value)
      // Boolean
      case 'anim':
        return toBoolean(value)
      // String
      case 'background':
      case 'compression':
      case 'fit':
      case 'format':
      case 'metadata':
      case 'origin-auth':
      default:
        return value
    }
  }

  /**
   * Build a regular expression to match a pattern at the start of a string
   * @param pattern
   */
  startsWithPattern(pattern: string): RegExp {
    return new RegExp(`^${this.escapeRegExp(pattern)}`)
  }

  /**
   * Escape a string for use in a regular expression
   * @param string
   */
  escapeRegExp(string: string): string {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  }

  /**
   * Verify the signature of the request to ensure it was signed by the shared secret key
   *
   * - The signature is checked against the pathname and query string of the URL, excluding the signature itself.
   * - Pathname should have with a leading `/` and no trailing `/`.
   *
   * @throws Error
   * @param url
   * @return Promise<boolean>
   */
  async verifySignature(url: URL): Promise<boolean> {
    if (!this.$verifyRequestSignatures) {
      return true
    }

    const encoder = new TextEncoder()
    const hash = url.searchParams.get(this.$verifyParam)

    if (!hash) {
      throw new Error(`URLs must have a valid ${this.$verifyParam} parameter`)
    }
    const key = await this.getKey(this.$signingSecret)

    url.searchParams.delete(this.$verifyParam)

    return await crypto.subtle.verify('HMAC', key, BufferHelper.hexStringToUint8Array(hash), encoder.encode(`${url.pathname}${url.search}`))
  }

  /**
   * Get a CryptoKey from a secret
   *
   * @param secret
   * @return Promise<CryptoKey>
   */
  async getKey(secret: string): Promise<CryptoKey> {
    return await crypto.subtle.importKey('raw', new TextEncoder().encode(this.$signingSecret), { name: 'HMAC', hash: 'SHA-256' }, true, [
      'verify',
    ])
  }
}
