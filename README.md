# Manticore Search
![example workflow](https://github.com/gordonbanderson/silverstripe-manticore-search/actions/workflows/php.yml/badge.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gordonbanderson/silverstripe-manticore-search/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/gordonbanderson/silverstripe-manticore-search/?branch=main)
[![codecov.io](https://codecov.io/github/gordonbanderson/silverstripe-manticore-search/coverage.svg?branch=main)](https://codecov.io/github/gordonbanderson/silverstripe-manticore-search?branch=main)


[![Latest Stable Version](https://poser.pugx.org/suilven/silverstripe-manticore-search/version)](https://packagist.org/packages/suilven/silverstripe-manticore-search)
[![Latest Unstable Version](https://poser.pugx.org/suilven/silverstripe-manticore-search/v/unstable)](//packagist.org/packages/suilven/silverstripe-manticore-search)
[![Total Downloads](https://poser.pugx.org/suilven/silverstripe-manticore-search/downloads)](https://packagist.org/packages/suilven/silverstripe-manticore-search)
[![License](https://poser.pugx.org/suilven/silverstripe-manticore-search/license)](https://packagist.org/packages/suilven/silverstripe-manticore-search)
[![Monthly Downloads](https://poser.pugx.org/suilven/silverstripe-manticore-search/d/monthly)](https://packagist.org/packages/suilven/silverstripe-manticore-search)
[![Daily Downloads](https://poser.pugx.org/suilven/silverstripe-manticore-search/d/daily)](https://packagist.org/packages/suilven/silverstripe-manticore-search)
[![composer.lock](https://poser.pugx.org/suilven/silverstripe-manticore-search/composerlock)](https://packagist.org/packages/suilven/silverstripe-manticore-search)

[![GitHub Code Size](https://img.shields.io/github/languages/code-size/gordonbanderson/silverstripe-manticore-search)](https://github.com/gordonbanderson/silverstripe-manticore-search)
[![GitHub Repo Size](https://img.shields.io/github/repo-size/gordonbanderson/silverstripe-manticore-search)](https://github.com/gordonbanderson/silverstripe-manticore-search)
[![GitHub Last Commit](https://img.shields.io/github/last-commit/gordonbanderson/silverstripe-manticore-search)](https://github.com/gordonbanderson/silverstripe-manticore-search)
[![GitHub Activity](https://img.shields.io/github/commit-activity/m/gordonbanderson/silverstripe-manticore-search)](https://github.com/gordonbanderson/silverstripe-manticore-search)
[![GitHub Issues](https://img.shields.io/github/issues/gordonbanderson/silverstripe-manticore-search)](https://github.com/gordonbanderson/silverstripe-manticore-search/issues)

![codecov.io](https://codecov.io/github/gordonbanderson/silverstripe-manticore-search/branch.svg?branch=main)

Search content in SilverStripe using manticoresearch as the free text search engine. 

## Install
### PHP
Via Composer

``` bash
$ composer require suilven/silverstripe-manticore-search
```

### Manticore Search
Packages are available for multiple platforms, see https://manticoresearch.com/downloads/ - version 3.5 is required
for compatibility with the ManticoreSearch PHP client.

Alternatively one can start an instance using docker:

```
docker run --name manticore -p 9306:9306 -p 9308:9308 -d manticoresearch/manticore
```

Note that does not include volume mapping in order to backup the indexed data.

## Configuration
### Indexing
See https://github.com/gordonbanderson/freetextsearch#configuration
### Manticoresearch Specific
By default, manticore is expected to be found on `127.0.0.1` on port `9308`.  To override this, add a config file
simiar to the following:

```yml
---
Name: manticore-my-host
After: manticore
---

Suilven\ManticoreSearch\Service\Client:
  host: 'manticoresearch-manticore'
  port: 19308
```

## Usage

See https://github.com/gordonbanderson/freetextsearch#usage

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ vendor/bin/phpunit tests '' flush=1
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email gordon.b.anderson@gmail.com instead of using the issue tracker.

## Credits

- [Gordon Anderson][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/suilven/silverstripe-manticore-search.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/suilven/silverstripe-manticore-search/main.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/suilven/silverstripe-manticore-search.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/suilven/silverstripe-manticore-search.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/suilven/silverstripe-manticore-search.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/suilven/silverstripe-manticore-search
[link-downloads]: https://packagist.org/packages/suilven/silverstripe-manticore-search
[link-author]: https://github.com/gordonbanderson
[link-contributors]: ../../contributors
