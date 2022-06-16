/*****************************************************************************
 * START CONFIG
 * @type {Object}
 *****************************************************************************
 */
const CONFIG = {
  /**
   * The base URL for image assets, will be prefixed to image path from
   * IMAGE_PARAM (see below)
   * 
   * @type {string} 
   */
  ORIGIN: "http:://assets.zaengle.com",
  /**
   * The URL param name containing the path to the image (should exclude the
   * origin hostname)
   * 
   * @type {String}
   */
  IMAGE_PARAM: "image",
  /**
   * Map of URL param names to CF Image Resizing Option names
   *
   * - Remove keys to disable an option
   * - Add a key to add an option (urlParam: cfOptionName)
   * - Rename a key to rename the URL param
   * 
   * @type {Object}
   * @see https://developers.cloudflare.com/images/image-resizing/url-format/
   */
  MAP_URL_PARAMS: {
    w: "width",
    h: "height",
    f: "format",
    q: "quality",
    dpr: "dpr",
    fit: "fit",
    gravity: "gravity",
    metadata: "metadata",
    anim: "anim",
    bg: "background",
    blur: "blur",
    brightness: "brightness",
    contrast: "contrast",
    gamma: "gamma",
    trim: "trim",
    rotate: "rotate",
    sharpen: "sharpen",
    
  },
  /**
   * [REJECT_XTN_PATTERN description]
   * @type {[type]}
   */
  REJECT_XTN_PATTERN: !/\.(jpe?g|png|gif|webp)$/i,

  /**
   * Headers to set on a successful request (<name> : <value>)
   * @type {Object}
   */
  SUCCESS_HEADERS: {
    // Set cache for 1 year
    "Cache-Control": "public, max-age=31536000, immutable",
    // Set Vary header
    "Vary": "Accept",
  },
  /**
   * Use signed urls
   */
  REQUIRE_SIGNED_URLS: true,
  SIGNATURE_PARAM: "signature",
  URL_SIGNING_SECRET: "9beb7f1a-5481-4223-b13e-e502ad6b29e1",
};
/*****************************************************************************
 * END CONFIG
 *****************************************************************************
 */


/**
 * Map query string parameters to request options.
 * @param  {URL} request url
 * @return {object}
 */
const searchParamsToOptions = (searchParams) => 
  Object.entries(CONFIG.MAP_URL_PARAMS).reduce(
    (result, [param, option]) => {
      if (searchParams.has(param)) {
        result[option] = searchParams.get(param);
      }
      return result;
    }, 
    {}
  );

/**
 * Handle Your automatic format negotiation via the Accept header.
 * @param  {URL} url     
 * @param  {[type]} request [description]
 * @return {[type]}         [description]
 */
const setImageFormat = (acceptHeader) => {
  const result = {};

  if (/image\/avif/.test(acceptHeader)) {
    result.format = 'avif';
  } else if (/image\/webp/.test(acceptHeader)) {
    result.format = 'webp';
  }

  return result;
};

/**
 * Generate the key from the secret
 * @param  {TextEncoder} encoder 
 * @param  {string} secret The secret shared with the calling service
 * @return {CryptoKey}
 */
async function getKey(encoder, secret) {
  return await crypto.subtle.importKey(
    'raw',
    encoder.encode(secret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['verify']
  );
}

/**
 * Get the data to authenticate against the HMAC
 *
 * We use the search params with the signature removed
 * 
 * @param  {SearchParams} allSearchParams
 * @param  {String} signatureParam  
 * @return {String}                 
 */
function getDataToAuthenticate(allSearchParams, signatureParam) {
  // Clone the URL params
  const searchParams = new URLSearchParams(allSearchParams.toString());
  // remove the signature param
  searchParams.delete(signatureParam);
  // serialize
  return `${searchParams.toString()}`;
}

/**
 * Validate that a signed URL has not been tampered with
 * @param  {URL} url
 * @param  {string} signature hmac from URL param
 * @return {boolean}
 */
async function validateSignedUrl(url, signature) {
  const encoder = new TextEncoder();

  return await crypto.subtle.verify(
    'HMAC',
    getKey(encoder, CONFIG.URL_SIGNING_SECRET),
    byteStringToUint8Array(atob(signature)),
    encoder.encode(
      getDataToAuthenticate(url.searchParams, CONFIG.SIGNATURE_PARAM)
    ),
  );
};

/**
 * Fetch and log a request
 * @param {Request} request
 */
async function handleRequest(request) {
  // Parse request URL to get access to query string
  const url = new URL(request.url);

  // Cloudflare-specific options are in the cf object.
  const options = { 
    cf: { 
      image: {
        ...searchParamsToOptions(url.searchParams),
        ...setImageFormat(request.headers.get("Accept")),
      },
    },
  };

  if (CONFIG.REQUIRE_SIGNED_URLS) {
    const signature = url.searchParams.get(CONFIG.SIGNATURE_PARAM);

    if (!signature) {
      return new Response(`URLs must be signed via the "${CONFIG.SIGNATURE_PARAM}" param`, { status: 400 });
    } else if (!validateSignedUrl(url, signature)) {
      return new Response(`URL signature is invalid`, { status: 403 });
    }
  }

   // Validate the request
  try {
    const imagePath = url.searchParams.get(CONFIG.IMAGE_PARAM);

    if (!imagePath) {
      return new Response(`Missing "${CONFIG.IMAGE_PARAM}" value`, { status: 400 });
    } else if (path.charAt(0) !== "/") {
      return new Response(`"${CONFIG.IMAGE_PARAM}" value must be an absolute path`, { status: 400 });
    }

    // Enforce the origin
    const { pathname } = new URL(`${CONFIG.ORIGIN}${imagePath}`);

    // Optionally, only allow URLs with JPEG, PNG, GIF, or WebP file extensions
    // @see https://developers.cloudflare.com/images/url-format#supported-formats-and-limitations
    if (REJECT_XTN_PATTERN.test(pathname)) {
      return new Response("Disallowed file extension", { status: 400 })
    }

  } catch (err) {
    return new Response(`Invalid "${CONFIG.IMAGE_PARAM}" value`, { status: 400 })
  }

  // Passes through request headers
  const imageRequest = new Request(imageURL, {
    headers: request.headers,
  });

  // Returning fetch() with resizing options will pass through response with the resized image.
  let response = await fetch(imageRequest, options);

  // Reconstruct the Response object to make its headers mutable.
  response = new Response(response.body, response);

  if (response.ok  || response.status == 304) {
    Object.entries(CONFG.SUCCESS_HEADERS).forEach(([name, value]) => {
      response.headers.set(name, value);
    });

    return response;
  } else {
    return new Response(`Could not fetch the image — the server returned HTTP error ${response.status}`, {
      status: 400,
      headers: {
        "Cache-Control": "no-cache"
      },
    });
  }
};

/**
 * Bind
 */
addEventListener("fetch", (event) => {
  event.respondWith(handleRequest(event.request));
});
