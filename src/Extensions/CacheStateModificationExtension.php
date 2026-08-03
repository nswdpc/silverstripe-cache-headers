<?php

namespace NSWDPC\Utilities\Cache;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;

/**
 * Check project configuration for controller cache state configuration
 * Currently supports private or disable cache (forced) states on specific controllers
 * @extends \SilverStripe\Core\Extension<(\SilverStripe\Control\Controller & static)>
 */
class CacheStateModificationExtension extends Extension
{
    public function onBeforeInit()
    {
        $configuration = CacheHeaderConfiguration::config()->get('controllers');
        if (!empty($configuration['privateCache'])) {
            $this->setPrivateState($configuration['privateCache']);
        }

        if (!empty($configuration['disableCache'])) {
            $this->setDisableState($configuration['disableCache']);
        }
    }

    /**
     * Match current controller against an array of controller names
     * @param array $controllers to check current controller against
     */
    protected function matchController(array $controllers): bool
    {
        $controllerCheck = (fn ($className, $k): bool => $this->getOwner() instanceof $className);
        $matches = array_filter($controllers, $controllerCheck, ARRAY_FILTER_USE_BOTH);
        return $matches !== [];
    }

    /**
     * Based on the controller and configuration, trigger a disabled cache state
     * in the application and tell the {@link CacheHeaderProxyMiddleware} to honour that
     * See config.yml for the configured list of controllers
     * @param array $controllers controllers that should have a disabled cache
     */
    protected function setDisableState(array $controllers)
    {
        if ($controllers === []) {
            // none configured
            return;
        }

        if ($match = $this->matchController($controllers)) {
            $cacheMiddleware = Injector::inst()->get(HTTPCacheControlMiddleware::class);
            $cacheMiddleware->disableCache(true)->useAppliedState();
        }
    }

    /**
     * Based on the controller and configuration, trigger a private cache state with
     * a max-age of 0 in the application
     * and tell the {@link CacheHeaderProxyMiddleware} to honour that
     * See config.yml for the configured list of controllers
     * @param array $controllers controllers that should have a private cache
     */
    protected function setPrivateState(array $controllers)
    {
        if ($controllers === []) {
            // none configured
            return;
        }

        if ($match = $this->matchController($controllers)) {
            $cacheMiddleware = Injector::inst()->get(HTTPCacheControlMiddleware::class);
            $cacheMiddleware->privateCache(true)->useAppliedState();
        }
    }
}
