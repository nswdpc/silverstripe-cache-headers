<?php

namespace NSWDPC\Utilities\Cache\Tests;

use NSWDPC\Utilities\Cache\CacheHeaderConfiguration;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Security\InheritedPermissions;
use Page;

require_once( dirname(__FILE__) . "/AbstractCacheTest.php" );

/**
 * Test with public caching and siteconfig varying settings
 */
class PublicCacheTest extends AbstractCacheTest {

    protected static $fixture_file = 'PublicCacheTest.yml';

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        PublicCachePage::class
    ];

    /**
     * @var int
     */
    protected $maxAge = 7301;

    /**
     * @var int
     */
    protected $sMaxAge = 7401;

    protected function setUp() : void
    {
        parent::setUp();

        // The test application has a configured public state by default
        CacheHeaderConfiguration::config()->set('state', 'public');
        CacheHeaderConfiguration::config()->set('max_age', $this->maxAge);
        CacheHeaderConfiguration::config()->set('s_max_age', $this->sMaxAge);
        CacheHeaderConfiguration::config()->set('must_revalidate', true);

    }


    /**
     * Test basic cache headers, header-test has a form
     */
    public function testCacheHeaders() {

        // page has form, this should disableCache
        $response = $this->get("header-test/");
        $body = $response->getBody();

        $this->assertTrue(strpos($body, "<h1>PUBLIC_CACHE_PAGE</h1>") !== false, "Content PUBLIC_CACHE_PAGE is not in response body");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);

        $this->assertFalse( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Page 1 - Header {$headers['cache-control']} has public state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache") && $this->hasCacheDirective($parts, "no-store"), "Page 1 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * Test basic cache headers where the page has no form
     */
    public function testCacheHeadersWithNoForm() {

        // request and do not include form
        $response = $this->get("header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Page 1 - Header {$headers['cache-control']} is public" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} has must-revalidate" );
        $this->assertTrue( $this->hasCacheDirective($parts, "s-maxage", "7401"), "Page 1 - Header {$headers['cache-control']} has s-maxage=7401");
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age", "7301"), "Page 1 - Header {$headers['cache-control']} has max-age=7301" );

    }

    /**
     * Test page with a form that is allowed to cache
     * formcache=1 -> disableSecurityToken, set method = GET
     * the result should be public caching
     */
    public function testCacheHeadersWithFormCache() {

        // request but turn off the security token and set to GET
        $response = $this->get("header-test/?formcache=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Page 1 - Header {$headers['cache-control']} is public" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} has must-revalidate" );
        $this->assertTrue( $this->hasCacheDirective($parts, "s-maxage" , "7401"), "Page 1 - Header {$headers['cache-control']} has s-maxage=7401");
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age", "7301"), "Page 1 - Header {$headers['cache-control']} has max-age=7301" );

    }

    /**
     * formcache=1 -> disableSecurityToken, set method = GET
     * Test with form disableCache turned off but with a session
     * Result should be private cache
     */
    public function testCacheHeadersWithFormCacheLoggedIn() {

        // log in - disable cache
        $this->logInWithPermission('ADMIN');
        // request but turn off the security token and set to GET
        $response = $this->get("header-test/?formcache=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, HTTPCacheControlMiddleware::STATE_PRIVATE), "Page 1 - Header {$headers['cache-control']} should be private" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 1 - Header {$headers['cache-control']} missing must-revalidate" );

    }

    /**
     * Tests based on CanViewType, with no form
     */
    public function testCanViewRootPage() {
        // Inherit (root page)
        $response = $this->get("/header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "public"), "Page 1 - Header {$headers['cache-control']} has public" );

    }

    /**
     * Tests with can view anyone
     */
    public function testCanViewSubPageAnyone() {
        // Anyone page under root should be public
        $response = $this->get("/header-test/sub-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 2 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "public"), "Page 2 - Header {$headers['cache-control']} has public" );
    }

    /**
     * Tests sub page with "only these users" restriction
     * Result should not be public cache
     */
    public function testCanViewSubPageOnlyTheseUsers() {
        // OnlyTheseUsers should not be public
        $response = $this->get("/header-test/restricted-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 3 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache") && $this->hasCacheDirective($parts, "no-store"), "Page 3 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 3 - Header {$headers['cache-control']} has must-revalidate" );
    }

    /**
     * Tests sub page with logged in user restriction
     * Result should not be public cache
     */
    public function testCanViewSubPageLoggedInUsers() {
        // LoggedInUsers page should not be public
        $response = $this->get("/header-test/loggedin-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 4 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache") && $this->hasCacheDirective($parts, "no-store"), "Page 4 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 4 - Header {$headers['cache-control']} has must-revalidate" );
    }

    /**
     * Test inherited view=anyone page
     */
    public function testCanViewSubPageInheritedAnyone() {
        // Inherited from parent page with Anyone - should bge public
        $response = $this->get("/header-test/sub-page-header-test/inherited-anyone-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 5 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "public"), "Page 5 - Header {$headers['cache-control']} has public" );
    }

    /**
     * Test inherited sub page, with OnlyTheseUsers permission
     * should not be public
     */
    public function testCanViewSubPageInheritedOnlyTheseUsers() {
        // Inherited from parent page with OnlyTheseUsers set - not public
        $response = $this->get("/header-test/restricted-page-header-test/inherited-restricted-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 6 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache") && $this->hasCacheDirective($parts, "no-store"), "Page 6 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 6 - Header {$headers['cache-control']} has must-revalidate" );
    }

    /**
     * Restrict SiteConfig to Logged In Users and test root page (Inherit)
     * Should not be public
     */
    public function testCanViewRestrictedRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/parentless-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 7 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache") && $this->hasCacheDirective($parts, "no-store"), "Page 7 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 7 - Header {$headers['cache-control']} has must-revalidate" );
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );
    }

    /**
     * Sub Page anyone, but restricted site config
     * Result should not be public cache
     */
    public function testCanViewRestrictedSubPageAnyone() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/header-test/sub-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 2 - must have a cache-control response header");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache") && $this->hasCacheDirective($parts, "no-store"), "Page 2 - Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 2 - Header {$headers['cache-control']} has must-revalidate" );
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );
    }

}
