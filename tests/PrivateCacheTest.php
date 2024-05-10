<?php

namespace NSWDPC\Utilities\Cache\Tests;

use NSWDPC\Utilities\Cache\CacheHeaderConfiguration;
use SilverStripe\Security\InheritedPermissions;
use Page;

require_once( dirname(__FILE__) . "/AbstractCacheTest.php" );

class PrivateCacheTest extends AbstractCacheTest {

    protected static $fixture_file = 'PrivateCacheTest.yml';

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        PrivateCachePage::class
    ];

    /**
     * @var int
     */
    protected $maxAge = 301;

    protected function setUp() : void
    {
        parent::setUp();
        // Test pages with a max-age and private cache, must-revalidate
        CacheHeaderConfiguration::config()->set('state', 'private');
        CacheHeaderConfiguration::config()->set('max_age', $this->maxAge);
        CacheHeaderConfiguration::config()->set('s_max_age', null);
        CacheHeaderConfiguration::config()->set('must_revalidate', true);
        CacheHeaderConfiguration::config()->set('no_cache', null);
        CacheHeaderConfiguration::config()->set('no_store', null);

    }

    /**
     * 1. SiteConfig = Anyone
     * 2. Root page with Anyone permission
     * 3. Private cache control headers per configuration
     */
    public function testPageHasCacheHeaderConfigurationValues() {
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );
        $response = $this->get("/anyone-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "private"), "Page 1 - Header {$headers['cache-control']} has private" );
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 1 - Header {$headers['cache-control']} missing max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Root page with Anyone permission
     * 3. Private cache control headers
     */
    public function testRestrictedSiteCanViewAnyoneRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/anyone-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "no-cache") && $this->hasCachingState($parts, "no-store"), "Page 1 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertFalse( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 1 - Header {$headers['cache-control']} missing max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} missing must-revalidate" );
    }


    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Root page with Anyone permission
     * 3. Private cache control headers
     */
    public function testRestrictedSiteCanViewInheritUnderAnyoneRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/anyone-root-page-test/inherit-anyone-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 3 - must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "no-cache") && $this->hasCachingState($parts, "no-store"), "Page 3 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertFalse( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 3 - Header {$headers['cache-control']} missing max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 3 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Root page with Inherit permission
     * 3. Result should be a restricted cache as this will redirect to log in
     */
    public function testRestrictedSiteCanViewInheritRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/inherit-root-page-test/");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 2 - must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "no-cache") && $this->hasCachingState($parts, "no-store"), "Page 2 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 2 - Header {$headers['cache-control']} has must-revalidate" );
    }

    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Sub page with Anyone permission, root has inherit permission (page 2)
     * 3. Result should be a private cache
     */
    public function testRestrictedSiteCanViewAnyoneUnderInheritRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/inherit-root-page-test/anyone-inherit-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 4 - must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "no-cache") && $this->hasCachingState($parts, "no-store"), "Page 4 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertFalse( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 4 - Header {$headers['cache-control']} no max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 4 - Header {$headers['cache-control']} has must-revalidate" );
    }

    /**
     * Unrestricted site and root but has logged in sub page
     */
    public function testLoggedInPageUnderUnrestricted() {
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        $response = $this->get("/anyone-root-page-test/loggedin-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 5 - must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "no-cache") && $this->hasCachingState($parts, "no-store"), "Page 5 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 5 - Header {$headers['cache-control']} has must-revalidate" );
    }

}
