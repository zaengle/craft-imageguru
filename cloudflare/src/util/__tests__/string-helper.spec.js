import { trimSlashes } from '../StringHelper'

describe('StringHelper.trimSlashes', () => {
  it('leaves slashes in the middle of a string intact', () => {
    expect(trimSlashes('foo/bar')).toBe('foo/bar')
  })
  it('removes leading slashes', () => {
    expect(trimSlashes('/foo/bar')).toBe('foo/bar')
  })
  it('removes trailing slashes', () => {
    expect(trimSlashes('foo/bar/')).toBe('foo/bar')
  })
  it('removes multiple slashes', () => {
    expect(trimSlashes('///foo/bar///')).toBe('foo/bar')
  })
})
