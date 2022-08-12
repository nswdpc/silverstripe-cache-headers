<?php

namespace NSWDPC\Utilities\Cache\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;

/**
 * Base test setup for cache headers tests
 */
abstract class AbstractCacheTest extends FunctionalTest {

    protected function setUp() : void
    {
        parent::setUp();
        Director::config()->update('alternate_base_url', '/');

        // Add test theme
        $themes = [
            "nswdpc/silverstripe-cache-headers:tests/templates",
            SSViewer::DEFAULT_THEME,
        ];
        SSViewer::set_themes($themes);

        foreach (\Page::get() as $page) {
            $page->publishSingle();
        }

        // ensure on the live stage
        Versioned::set_stage(Versioned::LIVE);

        // need to update default states as framework sets defaultState: 'disabled' in dev env
        Config::inst()->set(HTTPCacheControlMiddleware::class, 'defaultState', HTTPCacheControlMiddleware::STATE_ENABLED);
        Config::inst()->set(HTTPCacheControlMiddleware::class, 'defaultForcingLevel', 0);

        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        // intial request without session
        $this->logOut();

    }

    /**
     * Set site config CanViewType
     */
    protected function setSiteConfigCanViewType(string $type) {
        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->CanViewType = $type;
        $siteConfig->write();
    }

    protected function getCacheControlParts($header) : array {
        $directives = array_map("trim", explode("," , $header));
        $parts = [];
        foreach($directives as $directive) {
            $part = explode("=", $directive, 2);
            $parts[ $part[0] ] = isset($part[1]) ? $part[1] : null;
        }
        return $parts;
    }


    /**
     * Check a state exists in the cache control parts
     * @param array $parts cache control parts in key => value pairing
     * @param string $state eg private, public
     */
    protected function hasCachingState(array $parts, $state) : bool {
        return array_key_exists($state, $parts) !== false;
    }

    /**
     * Check a directive exists in the cache control parts, pass a value to check that as well
     * @param array $parts cache control parts in key => value pairing
     * @param string $directive
     * @param null $value, optional, check if the value of the directive matches this value passed in
     */
    protected function hasCacheDirective(array $parts, string $directive, $value = null) : bool {
        $exists = array_key_exists($directive, $parts);
        if(!$exists) {
            return false;
        } else if(!is_null($value)) {
            return $parts[ $directive ] == $value;
        } else {
            return true;
        }
    }
}
