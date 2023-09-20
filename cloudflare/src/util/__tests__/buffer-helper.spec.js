import { assertType, expectTypeOf } from 'vitest'
import { hexStringToUint8Array } from '../BufferHelper'

describe('BufferHelper.hexStringToUint8Array', () => {
  it('correctly converts a hex string to a Uint8Array', () => {
    const result = hexStringToUint8Array('0a0b0c0d0e0f')

    expect(result).toBeInstanceOf(Uint8Array)
    expect(result.byteLength).toBe(6)
    expect([...result]).toEqual([10, 11, 12, 13, 14, 15])
  })
})
