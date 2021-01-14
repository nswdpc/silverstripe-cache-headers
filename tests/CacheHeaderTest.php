<?php

namespace NSWDPC\Utilities\Cache\Tests;

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

    }

    public function testCacheHeaders() {

        // ensure on the live stage
        Versioned::set_stage(Versioned::LIVE);

        // need to update default states as framework disables in dev env
        Config::inst()->set(HTTPCacheControlMiddleware::class, 'defaultState', 'enabled');
        Config::inst()->set(HTTPCacheControlMiddleware::class, 'defaultForcingLevel', 0);

        // request without session
        $this->logOut();

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

        // request and do not include form
        $response = $this->get("header-test/?noform=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        /**
         * the test controller has the following set
         * [0] => public
         * [1] => must-revalidate
         * [2] => s-maxage=7201
         * [3] => max-age=7200
         */

        $parts = explode("," , $headers['cache-control']);
        $parts = array_map("trim", $parts);
        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Header {$headers['cache-control']} should be public" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
        $this->assertTrue( $this->hasCacheDirective($parts, "s-maxage=7201"), "Header {$headers['cache-control']} missing s-maxage=7201");
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age=7200"), "Header {$headers['cache-control']} missing max-age=7200" );

        // request but turn off the security token and set to GET
        // the result should be public caching
        $response = $this->get("header-test/?formcache=1");
        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        /**
         * the test controller has the following set
         * [0] => public
         * [1] => must-revalidate
         * [2] => s-maxage=7201
         * [3] => max-age=7200
         */

        $parts = explode("," , $headers['cache-control']);
        $parts = array_map("trim", $parts);
        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Header {$headers['cache-control']} should be public" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
        $this->assertTrue( $this->hasCacheDirective($parts, "s-maxage=7201"), "Header {$headers['cache-control']} missing s-maxage=7201");
        $this->assertTrue( $this->hasCacheDirective($parts, "max-age=7200"), "Header {$headers['cache-control']} missing max-age=7200" );

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

    private function hasCachingState(array $header, $state) {
        return array_search($state, $header) !== false;
    }

    private function hasCacheDirective(array $header, $directive) {
        return array_search($directive, $header) !== false;
    }

}
