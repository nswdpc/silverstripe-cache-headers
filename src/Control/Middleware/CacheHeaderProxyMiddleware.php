<?php
namespace NSWDPC\Utilities\Cache;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Injector\Injector;

/**
 * If the module configures specific cache control headers here
 * If the core sets specific cache states, these are respected with the application overriding these
 */
class CacheHeaderProxyMiddleware extends HTTPCacheControlMiddleware {

    /**
     * @var bool
     */
    protected $useAppliedState = false;

    /**
     * Flag that the applied state should be preferred
     * See {@link CacheStateModificationExtension} for an example
     */
    public function useAppliedState() : self {
        if($this->getState() == parent::STATE_PUBLIC) {
            Logger::log("Setting useAppliedState for public state will be ignored", "NOTICE");
            $this->useAppliedState = false;
        } else {
            $this->useAppliedState = true;
        }
        return $this;
    }

    /**
     * @inheritdoc
     * parent::augmentState() with an additional step where cache state is set
     * from configuration, provided the application has not set a state
     */
    protected function augmentState(HTTPRequest $request, HTTPResponse $response)
    {
        parent::augmentState($request, $response);

        // If a specific cache state was applied in the application
        // via self::useAppliedState(), this should be honoured
        if($this->useAppliedState && ($this->getState() != parent::STATE_PUBLIC)) {
            return;
        }

        // Apply state based on configuration
        // this is where the configuration from the module is applied
        $this->applyConfiguredState();

    }

    /**
     * Check if cache state has been modified beyond the default app/framwork configuration
     */
    public function inInitialState() : bool {
        return ($this->getState() == $this->config()->get('defaultState'))
            && (is_null($this->forcingLevel) || ($this->forcingLevel == $this->config()->get('defaultForcingLevel')));
    }

    /**
     * Set the state based on configuration. Certain states preclude use of configured
     * directive values.
     *
     * The state is not forced, this allows the application to force a state.
     *
     * Forced states with higher precedence will be used in preference to
     * any configured state you set
     *
     * Example: if the configured state is 'public' and an application applies
     * another higher precedence state, that state will be used
     *
     * @return void
     */
    protected function applyConfiguredState() {

        // if the state has been changed at all...
        if(!$this->inInitialState()) {
            // Logger::log("Not apply configured state as state has changed from default {$this->getState()}/{$this->forcingLevel}");
            return;
        }

        $state = CacheHeaderConfiguration::config()->get('state');
        $maxAge = CacheHeaderConfiguration::config()->get('max_age');
        $sharedMaxAge = CacheHeaderConfiguration::config()->get('s_max_age');
        $vary = CacheHeaderConfiguration::config()->get('vary');
        $mustRevalidate = CacheHeaderConfiguration::config()->get('must_revalidate');
        $noStore = CacheHeaderConfiguration::config()->get('no_store');
        $noCache = CacheHeaderConfiguration::config()->get('no_cache');

        switch($state) {
            case HTTPCacheControlMiddleware::STATE_PUBLIC:
                $this->publicCache(false);
                break;
            case HTTPCacheControlMiddleware::STATE_PRIVATE:
                $this->privateCache(false);
                break;
            case HTTPCacheControlMiddleware::STATE_DISABLED:
                $this->disableCache(false);
                break;
            default:
                $this->enableCache(false);
                break;
        }

        // Add/update Vary header value
        if(!is_null($vary)) {
            $this->setVary($vary);
        }
        // Add must-revalidate

        if(!is_null($mustRevalidate)) {
            $this->setMustRevalidate($mustRevalidate);
        }

        // Setting this value results in no-store, no-cache being removed
        if (!is_null($maxAge)) {
            $this->setMaxAge($maxAge);
        }

        // Setting this value results in no-store, no-cache being removed
        if(!is_null($sharedMaxAge)) {
            $this->setSharedMaxAge($sharedMaxAge);
        }

        // if no-cache is set, this will remove max-age directives
        if(!is_null($noCache)) {
            $this->setNoCache($noCache);
        }

        // If no-store is set, it will remove max-age directives
        if(!is_null($noStore)) {
            $this->setNoStore($noStore);
        }

    }
}
