# Image Guru for Craft CMS 4.x

[![Latest Version on Packagist](https://img.shields.io/packagist/v/zaengle/craft-imageguru.svg?style=flat-square)](https://packagist.org/packages/zaengle/craft-toolbelt)
[![Total Downloads](https://img.shields.io/packagist/dt/zaengle/craft-imageguru.svg?style=flat-square)](https://packagist.org/packages/zaengle/craft-toolbelt)
[![Treeware](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/zaengle/craft-imageguru)

> Let The Image Guru Streamline your Image Transforms

- Drop in replacement for native image transforms, providing off-server transforms across a range of image transform services
- Configurable per-environment (use different transformers in dev vs production) and per volume

## Installation

Via composer:

```bash
composer require zaengle/craft-toolbelt
php craft plugin/install toolbelt
```

## Usage

See the [docs](https://craft-imageguru.zaengle.com/). TLDR; Configure transformers per volume in `config/imageguru.php` (automatically created on install)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please note: This is primarily a Zaengle internal tool, so while PRs that add features will always be considered, contributions will be evaluated based on their fit with Zaengle's approach and priorities rather than other consumers. Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Zaengle Corp](https://github.com/zaengle)
- [Icon is "Style" by Andre from NounProject.com](https://thenounproject.com/icon/style-3099907/)

## License

License: MIT
Please see [License File](LICENSE.md) for more information.

## Treeware

You're free to use this package, but if it makes it to your production environment we would highly appreciate you buying the world a tree.

It’s now common knowledge that one of the best tools to tackle the climate crisis and keep our temperatures from rising above 1.5C is to plant trees. If you contribute to a forest you’ll be creating employment for local families and restoring wildlife habitats.

You can buy trees and read more about Treeware at [treeware.earth](https://plant.treeware.earth/zaengle/craft-imageguru)