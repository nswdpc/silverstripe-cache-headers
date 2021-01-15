<?php
namespace NSWDPC\Utilities\Cache;

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
     * Generate response for the given request
     *
     * @param HTTPRequest $request
     * @param callable $delegate
     * @return HTTPResponse
     * @throws HTTPResponse_Exception
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = parent::process($request, $delegate);
        return $response;
    }

    /**
     * Update state based on current request and response objects
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     */
    protected function augmentState(HTTPRequest $request, HTTPResponse $response)
    {

        // Errors disable cache (unless some errors are cached intentionally by usercode)
        if ($response->isError() || $response->isRedirect()) {
            // Even if publicCache(true) is specified, errors will be uncacheable
            $this->disableCache(true);
        }

        if ($request->getSession()->getAll()) {
            // session is active but may be on a publicly cacheable page
            if($this->getState() == parent::STATE_DISABLED) {
                // request already set state to disabled - noop
                return;
            }

            if($this->getState() == parent::STATE_PRIVATE) {
                // request already set the state to private
                return;
            }
        }

        /**
         * Can apply configured state, without forcing it
         * this will allow code forcing a state OR
         * those states with higher precedence
         * to take precedence
         */
         $this->applyConfiguredState();

    }

    /**
     * Set the state based on configuration
     * Use configuration to modify the state and directives
     * @return void
     */
    protected function applyConfiguredState() {
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
