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

    protected function setUp()
    {
        parent::setUp();
        // Test pages with a max-age and private cache, must-revalidate
        CacheHeaderConfiguration::config()->set('state', 'private');
        CacheHeaderConfiguration::config()->set('max_age', $this->maxAge);
        CacheHeaderConfiguration::config()->set('s_max_age', null);
        CacheHeaderConfiguration::config()->set('must_revalidate', true);

    }

    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Root page with Anyone permission
     * Result should match configuration
     */
    public function testCanViewAnyoneRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/anyone-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "private"), "Page 1 - Header {$headers['cache-control']} missing private state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 1 - Header {$headers['cache-control']} missing max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} missing must-revalidate" );
    }


    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Root page with Anyone permission
     * Result should match configuration
     */
    public function testCanViewInheritUnderAnyoneRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/anyone-root-page-test/inherit-anyone-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 3 - no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "private"), "Page 3 - Header {$headers['cache-control']} missing private state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 3 - Header {$headers['cache-control']} missing max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 3 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Root page with Inherit permission
     * Result should be a restricted cache as this will redirect to log in
     */
    public function testCanViewInheritRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/inherit-root-page-test/");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 2 - no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 2 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 2 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 2 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * 1. Restrict SiteConfig =  Logged in users
     * 2. Sub page with Anyone permission, root has inherit permission (page 2)
     * Result should match configuration
     */
    public function testCanViewAnyoneUnderInheritRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/inherit-root-page-test/anyone-inherit-root-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 4 - no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, "private"), "Page 4 - Header {$headers['cache-control']} missing private state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age", $this->maxAge), "Page 4 - Header {$headers['cache-control']} missing max-age" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 4 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * Unrestricted site and root but has logged in sub page
     */
    public function testLoggedInPageUnderUnrestricted() {
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        $response = $this->get("/anyone-root-page-test/loggedin-page-test/");
        $headers = $response->getHeaders();

        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 2 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 2 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 2 - Header {$headers['cache-control']} missing must-revalidate" );
    }

}
