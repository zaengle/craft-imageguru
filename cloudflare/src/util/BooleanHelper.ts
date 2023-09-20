export function toBoolean(value: any): boolean {
  if (typeof value === 'boolean') {
    return value
  }
  if (typeof value === 'number') {
    return value !== 0
  }
  if (value instanceof Array) {
    return value.length > 0
  }

  if (typeof value === 'string') {
    switch (value.toLowerCase().trim()) {
      case 'true':
      case 'yes':
      case 'y':
      case '1':
        return true
      case 'false':
      case 'no':
      case 'n':
      case '0':
        return false
    }
  }
  return !!value
}
export default {
  toBoolean,
}
