<?php

declare(strict_types=1);

namespace NSWDPC\Utilities\Cache;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Configuraton class for setting and override header behaviour
 * Setting null values for a directive option means it will not be set
 * @author James
 */
class CacheHeaderConfiguration
{
    use Configurable;
    use Extensible;
    use Injectable;

    /**
     * @config
     */
    private static string $state = 'enabled';

    /**
     * @config
     */
    private static $max_age;

    /**
     * @config
     */
    private static $s_max_age;

    /**
     * @config
     */
    private static $must_revalidate;

    /**
     * @config
     */
    private static $vary;

    /**
     * @config
     */
    private static $no_store;

    /**
     * @config
     */
    private static $no_cache;

    /**
     * @config
     */
    private static array $controllers = [];

}
