/**
 * Trim slashes from the beginning and end of a string.
 * @param str
 */
export const trimSlashes = (str: string): string => str.replace(/^\/+|\/+$/g, '')

export default {
  trimSlashes,
}
