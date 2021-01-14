<?php

namespace NSWDPC\Utilities\Cache;

use NSWDPC\Shindig\Logger;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

/**
 * Sets relevant cache headers based on configuration
 * @author James
 */
class CacheHeaderExtension extends Extension {

    /**
     * Set the cache headers early, allowing controllers to override
     * @return void
     */
    public function onBeforeInit() {
        $this->setState();
    }

    /**
     * Set the state based on configuration
     * Use configuration to modify the state and directives
     * @return void
     */
    protected function setState() {
        $configuration = Injector::inst()->create(CacheHeaderConfiguration::class);

        $state = $configuration->config()->get('state');
        $max_age = $configuration->config()->get('max_age');
        $s_max_age = $configuration->config()->get('s_max_age');
        $vary = $configuration->config()->get('vary');
        $must_revalidate = $configuration->config()->get('must_revalidate');
        $no_store = $configuration->config()->get('no_store');

        $mw = HTTPCacheControlMiddleware::singleton();

        if(!is_null($vary)) {
            $mw->setVary($vary);
        }
        if(!is_null($s_max_age)) {
            $mw->setSharedMaxAge($s_max_age);
        }
        if(!is_null($must_revalidate)) {
            $mw->setMustRevalidate($must_revalidate);
        }
        if(!is_null($no_store)) {
            $mw->setNoStore($no_store);
        }

        switch($state) {
            case HTTPCacheControlMiddleware::STATE_PUBLIC:
                $mw->publicCache(false, $max_age);
                break;
            case HTTPCacheControlMiddleware::STATE_PRIVATE:
                $mw->privateCache(false);
                break;
            case HTTPCacheControlMiddleware::STATE_DISABLED:
                $mw->disableCache(false);
                break;
            default:
                $mw->enableCache(false, $max_age);
                break;
        }
    }
}
