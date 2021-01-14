<?php

namespace NSWDPC\Utilities\Cache;

use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Configuraton class for setting and override header behaviour
 * Setting null values for a directive option means it will not be set
 * @author James
 */
class CacheHeaderConfiguration {

    use Configurable;
    use Extensible;
    use Injectable;

    private static $state = 'enabled';

    private static $max_age = null;
    private static $s_max_age = null;
    private static $must_revalidate = null;
    private static $vary = null;
    private static $no_store = null;
    private static $no_cache = null;

}
