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

    /**
     * @var string
     * @config
     */
    private static $state = 'enabled';

    /**
     * @config
     */
    private static $max_age = null;

    /**
     * @config
     */
    private static $s_max_age = null;

    /**
     * @config
     */
    private static $must_revalidate = null;

    /**
     * @config
     */
    private static $vary = null;

    /**
     * @config
     */
    private static $no_store = null;

    /**
     * @config
     */
    private static $no_cache = null;

    /**
     * @config
     */
    private static $controllers = [];

}
