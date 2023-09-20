import { toBoolean } from '../BooleanHelper'
describe('BooleanHelper.toBoolean', () => {
  it('passes through a boolean', () => {
    expect(toBoolean(true)).toBe(true)
  })
  it('converts a string to a boolean', () => {
    expect(toBoolean('true')).toBe(true)
    expect(toBoolean('1')).toBe(true)
    expect(toBoolean('yes')).toBe(true)
    expect(toBoolean('y')).toBe(true)
    expect(toBoolean('false')).toBe(false)
    expect(toBoolean('0')).toBe(false)
    expect(toBoolean('no')).toBe(false)
    expect(toBoolean('n')).toBe(false)
  })
  it ('converts a number greater than one to true', () => {
    expect(toBoolean(2)).toBe(true)
  })
  it ('converts a number less than one to false', () => {
    expect(toBoolean(0)).toBe(false)
  })

  it('coverts an object to true', () => {
    expect(toBoolean({})).toBe(true)
  })

  it('converts an empty array to false', () => {
    expect(toBoolean([])).toBe(false)
  })
  it('converts a non-empty array to true', () => {
    expect(toBoolean([1])).toBe(true)
  })
})
