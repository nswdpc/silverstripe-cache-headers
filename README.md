# Cache header support for Silverstripe


Default Silverstripe cache handling [sends headers that are not considered cacheable](https://docs.silverstripe.org/en/4/developer_guides/performance/http_cache_headers/) by a proxy such as Cloudflare.

This module allows you to modify this behaviour via configuration, allowing a proxy to cache based on the headers sent by the application.

> This module is in development and is not yet suitable for production environments

### Usage

- Install this extension using composer
- Modify the configuration rules to your requirements
- Test behind your caching proxy and deploy

## Installation

Install via composer:
```
composer require nswdpc/silverstripe-cache-headers
```

## Documentation

The priority of caching directives in Silverstripe [are in this order as follows](https://docs.silverstripe.org/en/4/developer_guides/performance/http_cache_headers/#priority):

```
disableCache($force=true)
privateCache($force=true)
publicCache($force=true)
enableCache($force=true)
disableCache()
privateCache()
publicCache()
enableCache()
```

By default this module enables the cache (enableCache), but does not provide the force parameter as `true`.

See [documentation](./docs/en/001_index.md) for a primer on various options, including sample configurations.

## License

[BSD-3-Clause](./LICENSE.md)

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)


## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
