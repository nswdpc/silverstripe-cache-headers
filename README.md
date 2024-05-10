# Cache header support for Silverstripe

The default Silverstripe cache handling sends headers that are not considered cacheable by a proxy such as Cloudflare.

This module allows you to modify this behaviour via configuration, allowing a proxy to cache based on the headers sent by the application.

## Useful information

- [HTTP cache headers in Silverstripe](https://docs.silverstripe.org/en/4/developer_guides/performance/http_cache_headers/)
- [MDN Cache-Control reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control)

## Usage

- Install this extension using composer
- Modify the configuration rules to your requirements
- Test behind your caching proxy to verify Cache-Control and related header values are as expected

## Installation

Install via composer:

```sh
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

## Security

If you have found a security issue with this module, please email digital[@]dpc.nsw.gov.au in the first instance, detailing your findings.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
