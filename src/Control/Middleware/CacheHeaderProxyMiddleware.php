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
    public function useAppliedState() {
        $this->useAppliedState = true;
        return $this;
    }

    /**
     * @inheritdoc
     * Performs much the same as parent::augmentState() with the exception of
     * a configired public/enabled cache being honoured when a session is active,
     * which your application needs to handle
     */
    protected function augmentState(HTTPRequest $request, HTTPResponse $response)
    {

        // Errors and redirects disable cache
        if ($response->isError() || $response->isRedirect()) {
            $this->disableCache(true);
        }

        // Session is active
        if ($request->getSession()->getAll()) {
            // Honour private/disabled cache with active session
            if( in_array($this->getState(), [ parent::STATE_DISABLED, parent::STATE_PRIVATE ]) ) {
                return;
            }
        }

        // If a specific cache state was applied in the application via self::useAppliedState()
        // this should be honoured
        if($this->useAppliedState) {
            return;
        }

        // Apply state based on configuration
        $this->applyConfiguredState();

    }

    /**
     * Set the state based on configuration
     * Use configuration to modify the state and directives
     * The state is not forced, this allows the application to force a state
     * Forced states with higher precedence will be used in preference to
     * any configured state you set
     * Example: if the configured state is 'public' and an application applies another state
     * that state will be used, as publicCache has the lowest precedence
     * @return void
     */
    protected function applyConfiguredState() {

        $state = CacheHeaderConfiguration::config()->get('state');
        $maxAge = CacheHeaderConfiguration::config()->get('max_age');
        $sharedMaxAge = CacheHeaderConfiguration::config()->get('s_max_age');
        $vary = CacheHeaderConfiguration::config()->get('vary');
        $mustRevalidate = CacheHeaderConfiguration::config()->get('must_revalidate');
        $noStore = CacheHeaderConfiguration::config()->get('no_store');

        if(!is_null($vary)) {
            $this->setVary($vary);
        }
        if(!is_null($sharedMaxAge)) {
            $this->setSharedMaxAge($sharedMaxAge);
        }
        if(!is_null($mustRevalidate)) {
            $this->setMustRevalidate($mustRevalidate);
        }
        if(!is_null($noStore)) {
            $this->setNoStore($noStore);
        }

        switch($state) {
            case HTTPCacheControlMiddleware::STATE_PUBLIC:
                $this->publicCache(false, $maxAge);
                break;
            case HTTPCacheControlMiddleware::STATE_PRIVATE:
                $this->privateCache(false);
                if (!is_null($maxAge)) {
                    $this->setMaxAge($maxAge);
                }
                break;
            case HTTPCacheControlMiddleware::STATE_DISABLED:
                $this->disableCache(false);
                break;
            default:
                $this->enableCache(false, $maxAge);
                break;
        }

    }
}
