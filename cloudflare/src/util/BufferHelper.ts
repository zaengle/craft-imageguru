/**
 * Converts a hex string to a Uint8Array.
 * @param hexString
 */
export const hexStringToUint8Array = (hexString: string): Uint8Array =>
  Uint8Array.from(hexString.match(/[0-9a-f]{2}/gi) ?? [], (char) => parseInt(String(char), 16))

export default {
  hexStringToUint8Array,
}
