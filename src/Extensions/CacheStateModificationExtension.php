<?php
namespace NSWDPC\Utilities\Cache;

use SilverStripe\Core\Extension;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;

/**
 * Check project configuration for controller cache state configuration
 * Currently supports disabling cache (forced) on specific controllers
 */
class CacheStateModificationExtension extends Extension {

    public function onBeforeInit() {
        $configuration = CacheHeaderConfiguration::config()->get('controllers');
        if(!empty($configuration['disableCache'])) {
            $this->setDisableState($configuration['disableCache']);
        }
    }

    /**
     * Based on the controller and configuration, trigger a disabled cache state
     * in the application and tell the {@link CacheHeaderProxyMiddleware} to honour that
     * See config.yml for the configured list of controllers
     * @param array $controllers controllers that should have a disabled cache
     */
    protected function setDisableState(array $controllers) {
        if(empty($controllers)) {
            // none configured
            return;
        }
        $cacheMiddleware = HTTPCacheControlMiddleware::singleton();
        $controllerClass = get_class($this->owner);
        // Handle exact match on the controller class
        if(in_array($controllerClass, $controllers)) {
            $cacheMiddleware->disableCache(true)->useAppliedState();
            return;
        }
        // Include subclasses of the controller class
        foreach($controllers as $className) {
            if(is_subclass_of($controllerClass, $className, true)) {
                $cacheMiddleware->disableCache(true)->useAppliedState();
                return;
            }
        }
    }
}
