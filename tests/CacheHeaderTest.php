<?php

namespace NSWDPC\Utilities\Cache\Tests;

use NSWDPC\Utilities\Cache\CacheHeaderConfiguration;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Models\SiteTree;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\SSViewer;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\InheritedPermissions;
use Page;

class CacheHeaderTest extends FunctionalTest {

    protected static $fixture_file = 'CacheHeaderTest.yml';

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        CacheHeaderTestPage::class
    ];

    protected function setUp()
    {
        parent::setUp();
        Director::config()->update('alternate_base_url', '/');

        // Add test theme
        $themes = [
            "nswdpc/silverstripe-cache-headers:tests/templates",
            SSViewer::DEFAULT_THEME,
        ];
        SSViewer::set_themes($themes);

        foreach (Page::get() as $page) {
            $page->publishSingle();
        }

        // ensure on the live stage
        Versioned::set_stage(Versioned::LIVE);

        // need to update default states as framework sets defaultState: 'disabled' in dev env
        Config::inst()->set(HTTPCacheControlMiddleware::class, 'defaultState', HTTPCacheControlMiddleware::STATE_ENABLED);
        Config::inst()->set(HTTPCacheControlMiddleware::class, 'defaultForcingLevel', 0);

        // The test application has a configured public state by default
        CacheHeaderConfiguration::config()->set('state', 'public');
        CacheHeaderConfiguration::config()->set('max_age', 7301);
        CacheHeaderConfiguration::config()->set('s_max_age', 7401);
        CacheHeaderConfiguration::config()->set('must_revalidate', true);

        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        // intial request without session
        $this->logOut();

    }

    /**
     * Set site config CanViewType
     */
    private function setSiteConfigCanViewType(string $type) {
        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->CanViewType = $type;
        $siteConfig->write();
    }

    public function testCacheHeaders() {

        // page has form, this should disableCache
        $response = $this->get("header-test/");
        $body = $response->getBody();

        $this->assertTrue(strpos($body, "<h1>CACHE_HEADER_TEST_PAGE</h1>") !== false, "Content CACHE_HEADER_TEST_PAGE is not in response body");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        $parts = explode("," , $headers['cache-control']);
        $parts = array_map("trim", $parts);

        $this->assertFalse( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Header {$headers['cache-control']} has public state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
    }

    public function testCacheHeadersWithNoForm() {

        // request and do not include form
        $response = $this->get("header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        /**
         * the test controller has the following set
         * [0] => public
         * [1] => must-revalidate
         * [2] => s-maxage=7401
         * [3] => max-age=7301
         */

        $parts = explode("," , $headers['cache-control']);
        $parts = array_map("trim", $parts);
        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Header {$headers['cache-control']} should be public" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
        $this->assertTrue( $this->hasCacheDirective($parts, "s-maxage=7401"), "Header {$headers['cache-control']} missing s-maxage=7401");
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age=7301"), "Header {$headers['cache-control']} missing max-age=7301" );

    }

    // formcache=1 -> disableSecurityToken, set method = GET
    public function testCacheHeadersWithFormCache() {

        // request but turn off the security token and set to GET
        // the result should be public caching
        $response = $this->get("header-test/?formcache=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        /**
         * the test controller has the following set
         * [0] => public
         * [1] => must-revalidate
         * [2] => s-maxage=7401
         * [3] => max-age=7301
         */

        $parts = explode("," , $headers['cache-control']);
        $parts = array_map("trim", $parts);
        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Header {$headers['cache-control']} should be public" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
        $this->assertTrue( $this->hasCacheDirective($parts, "s-maxage=7401"), "Header {$headers['cache-control']} missing s-maxage=7401");
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age=7301"), "Header {$headers['cache-control']} missing max-age=7301" );

    }

    // formcache=1 -> disableSecurityToken, set method = GET
    // Test with form disableCache turned off but with a session
    public function testCacheHeadersWithFormCacheLoggedIn() {

        // log in - disable cache
        $this->logInWithPermission('ADMIN');
        // request but turn off the security token and set to GET
        $response = $this->get("header-test/?formcache=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");
        $parts = explode("," , $headers['cache-control']);
        $parts = array_map("trim", $parts);
        $this->assertFalse( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PRIVATE), "Header {$headers['cache-control']} should be private" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );

    }

    /**
     * Tests based on CanViewType
     */
    public function testCanViewRootPage() {
        // Inherit (root page)
        $response = $this->get("/header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 1 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "public"), "Page 1 - Header {$headers['cache-control']} missing public" );

    }

    public function testCanViewSubPageAnyone() {
        // Anyone page under root should be public
        $response = $this->get("/header-test/sub-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 2 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "public"), "Page 2 - Header {$headers['cache-control']} missing public" );
    }

    public function testCanViewSubPageOnlyTheseUsers() {
        // OnlyTheseUsers should not be public
        $response = $this->get("/header-test/restricted-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 3 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 3 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 3 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 3 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    public function testCanViewSubPageLoggedInUsers() {
        // LoggedInUsers page should not be public
        $response = $this->get("/header-test/loggedin-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 4 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 4 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 4 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 4 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    public function testCanViewSubPageInheritedAnyone() {
        // Inherited from parent page with Anyone - should bge public
        $response = $this->get("/header-test/sub-page-header-test/inherited-anyone-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 5 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "public"), "Page 5 - Header {$headers['cache-control']} missing public" );
    }

    public function testCanViewSubPageInheritedOnlyTheseUsers() {
        // Inherited from parent page with OnlyTheseUsers set - not public
        $response = $this->get("/header-test/restricted-page-header-test/inherited-restricted-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 6 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 6 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 6 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 6 - Header {$headers['cache-control']} missing must-revalidate" );
    }

    /**
     * Restrict SiteConfig to Logged In Users and test root page (Inherit)
     */
    public function testCanViewRestrictedRootPage() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/parentless-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 7 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 7 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 7 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 7 - Header {$headers['cache-control']} missing must-revalidate" );
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );
    }

    /**
     * Sub Page anyone, but restricted site config - not public
     */
    public function testCanViewRestrictedSubPageAnyone() {
        $this->setSiteConfigCanViewType( InheritedPermissions::LOGGED_IN_USERS );
        $response = $this->get("/header-test/sub-page-header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "Page 2 - no cache-control header in response");
        $parts = $this->getCacheControlParts($headers['cache-control']);
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Page 2 - Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Page 2 - Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Page 2 - Header {$headers['cache-control']} missing must-revalidate" );
        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );
    }

    private function getCacheControlParts($header) {
        $parts = explode("," , $header);
        $parts = array_map("trim", $parts);
        return $parts;
    }

    private function hasCachingState(array $header, $state) {
        return array_search($state, $header) !== false;
    }

    private function hasCacheDirective(array $header, $directive) {
        return array_search($directive, $header) !== false;
    }

}
