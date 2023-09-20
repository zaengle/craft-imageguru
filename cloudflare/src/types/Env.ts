export default interface Env {
  // The base origin URL of the upstream image source
  ORIGIN: string
  // The route prefix to match for image resizing requests
  ROUTE: string
  // The shared secret used to sign requests
  SHARED_SIGNING_SECRET?: string
  // Whether to verify request signatures
  VERIFY_REQUESTS: boolean
}
