# Documentation


## Standard disallow cache handling in Silvertripe

The following will call disableCache in `SilverStripe\Control\Middleware\HTTPCacheControlMiddleware`

+ Site is in dev environment
+ URLs viewed on the draft stage (stage=Stage)
+ CMS previewable URLs
+ Draft preview links (via the `silverstripe/sharedraftcontent` module)
+ Forms in certain contexts (see below)
+ An HTTP error code or redirect being returned (30x, 4xx, 5xx)
+ A session being active
+ Any specific controller caching directives you set or force
+ Security controller methods, such as ping()

Note that modules in your project may also modify cache handling.

## Forms

By default, forms will disable caching if:

+ the form submits via POST (default)
+ it has a CSRF token active (default)
+ A form has a validator that is not RequiredFields
+ A form has a RequiredFields validator with required fields

## Cloudflare

[Documentation](https://support.cloudflare.com/hc/en-us/articles/115003206852-Understanding-Origin-Cache-Control)

Enable caching for public pages by default when using Cloudflare:

```
---
Name: 'app-cache-headers'
After:
  - '#nswdpc-cache-headers'
---
NSWDPC\Utilities\Cache\CacheHeaderConfiguration:
  # add 'public' to Cache-Control
  state: 'public'
  # max age
  max_age: 7200
  # shared max age
  s_max_age: 7200
```
The resulting header will look like:

```
Cache-Control: public, must-revalidate, s-maxage=7200, max-age=7200
```
The tells Cloudflare the response from an origin server can be cached, for up to 7200s after which the resource becomes stale.

In Cloudflare, you can set [the Browser Cache TTL option](https://support.cloudflare.com/hc/en-us/articles/115003206852-Understanding-Origin-Cache-Control) to override the max-age:

> Cloudflare respects whichever value is higher: the Browser Cache TTL in Cloudflare or the max-age header.

The `s-max-age` header affects the Edge Cache TTL

> Simultaneously specify a Cloudflare Edge Cache TTL different than a browser’s cache TTL respectively via the s-maxage and max-age Cache-Control headers.  
