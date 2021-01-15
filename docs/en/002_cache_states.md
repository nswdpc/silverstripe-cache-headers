## Cache states

In precedence

## disableCache (forced)

+ Error or redirect (forced)
+ URLSpecialsMiddleware (forced)
+ Flushed requests (forced)
+ CMSPreview || stage=Stage (forced)


## privateCache (forced)

+ User code (with force param)

## publicCache (forced)


## enableCache (forced)


## disableCache

+ LeftAndMain
+ ShareDraftController (if installed)
+ Forms with canBeCached() returning false

+ HTTP::disable_http_cache is set (default=false)
+ HTTP::cache_ajax_requests is not set (default=false)
+ Security ping()

## privateCache

+ Session is active
+ User code (without force param)

## publicCache


## enableCache
