import CloudflareImageResizingHandler from '../CloudflareImageResizingHandler';

const handlerFactory = (ENV = {}) =>
  new CloudflareImageResizingHandler({
    ORIGIN: 'https://example.com',
    SHARED_SIGNING_SECRET: 'ilovepotatoes',
    ...ENV,
  })

describe('CloudflareImageResizingHandler', () => {
  describe('getValidOriginImageUrl', () => {
    const handler = handlerFactory()

    it('requires a path', () => {
      expect(() => handler.getValidOriginImageUrl('')).toThrow('A path is required')
    })

    it('requires a valid image file extension', () => {
      expect(() => handler.getValidOriginImageUrl('/path/to/image')).toThrowError('Not an allowed file extension')
      expect(() => handler.getValidOriginImageUrl('/path/to/image.pdf')).toThrow('Not an allowed file extension')
    })

    it('prepends the origin', () => {
      expect(handler.getValidOriginImageUrl('/path.jpg')).toEqual(new URL('https://example.com/path.jpg'))
    })
  })

  describe('verifySignature', () => {
    describe('when disabled', () => {
      const handler = handlerFactory({
        VERIFY_REQUESTS: false,
      })
      const url = new URL('https://example.com/path.jpg?width=100')
      it('does not require a signed request', () => {
        expect(handler.verifySignature(url)).resolves.toBe(true)
      })
    })

    describe('when enabled', () => {
      const handler = handlerFactory()
      const signature = 'f89a25e1a045fcc879d26da09c84d2bc90fbda4f9215f69f6c028d8b5b10fef1'
      it('requires a signature param ', () => {
        expect(() => handler.verifySignature(new URL('https://example.com/path.jpg?width=100'))).rejects.toThrow('URLs must have a valid verify parameter')
      })
      it('verifies the signature', () => {
        expect(handler.verifySignature(new URL(`https://example.com/path.jpg?width=100&verify=${signature}`))).resolves.toBe(true)
      })
    })

  })

  describe('getKey', async () => {
    const handler = handlerFactory({
      SHARED_SIGNING_SECRET: 'ilovepotatoes',
    })
    const key = await handler.getKey(handler.$signingSecret)
    const exportedKey = await window.crypto.subtle.exportKey('jwk', key)

    it('returns the expected key + config for the secret', () => {
      expect(exportedKey).toEqual({
        key_ops: [ 'verify' ],
        ext: true,
        kty: 'oct',
        k: 'aWxvdmVwb3RhdG9lcw',
        alg: 'HS256'
      })
    })
  });

  describe('coerceUrlParameter', () => {
    const handler = handlerFactory()

    it('Coerces the correct parameters to integers', () => {
      expect(handler.coerceUrlParameter('width', '100')).toBe(100)
      expect(handler.coerceUrlParameter('quality', '50')).toBe(50)
      expect(handler.coerceUrlParameter('rotate', '180')).toBe(180)
      expect(handler.coerceUrlParameter('height', '100')).toBe(100)
    })
    it('Coerces the correct parameters to floats', () => {
      expect(handler.coerceUrlParameter('blur', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('brightness', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('contrast', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('dpr', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('gamma', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('sharpen', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('fp-x', '0.5')).toBe(0.5)
      expect(handler.coerceUrlParameter('fp-y', '0.5')).toBe(0.5)
    })
    it('Coerces the correct parameters to booleans', () => {
      expect(handler.coerceUrlParameter('anim', 'true')).toBe(true)
    })
    it('Passes through other parameters unchanged', () => {
      expect(handler.coerceUrlParameter('background', 'value')).toBe('value')
      expect(handler.coerceUrlParameter('compression', 'value')).toBe('value')
      expect(handler.coerceUrlParameter('fit', 'value')).toBe('value')
      expect(handler.coerceUrlParameter('format', 'value')).toBe('value')
      expect(handler.coerceUrlParameter('metadata', 'value')).toBe('value')
      expect(handler.coerceUrlParameter('origin-auth', 'value')).toBe('value')
      expect(handler.coerceUrlParameter('non-existent-param', 'value')).toBe('value')
    })
  });

  describe('getTransform', () => {
    const handler = handlerFactory()

    const defaultParams = {
      anim: false,
      fit: 'crop',
      gravity: 'auto',
      quality: 80,
    }

    Object.freeze(defaultParams)

    it('applies the default params', () => {
      const transform = handler.getTransform(new URLSearchParams(''), new window.Headers())

      expect(transform).toEqual(defaultParams)
    })


    it('ignores unknown parameters', () => {
      const transform = handler.getTransform(new URLSearchParams('&non-existent-param=value'), new window.Headers())
      expect(transform).toEqual(defaultParams)
    })

    it('applies the correct parameters', () => {
      const params = {
        anim: 'true',
        background: '#000',
        blur: '0.5',
        brightness: '0.1',
        compression: 'fast',
        contrast: "2.0",
        dpr: 2,
        fit: 'contain',
        format: 'avif',
        gamma: '2.3',
        gravity: 'auto',
        height: '100',
        quality: '50',
        rotate: '180',
        sharpen: '2.1',
        width: '200',
      }
      const transform = handler.getTransform(new URLSearchParams(params), new window.Headers())

      expect(transform).toEqual({
        anim: true,
        background: '#000',
        blur: 0.5,
        brightness: 0.1,
        compression: 'fast',
        contrast: 2,
        dpr: 2,
        fit: 'contain',
        format: 'avif',
        gamma: 2.3,
        gravity: 'auto',
        height: 100,
        quality: 50,
        rotate: 180,
        sharpen: 2.1,
        width: 200
      })
    })
  })
});
