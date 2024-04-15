<?php

namespace NSWDPC\Utilities\Cache\Tests;

use NSWDPC\Utilities\Cache\CacheHeaderConfiguration;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Security\InheritedPermissions;
use Page;

require_once( dirname(__FILE__) . "/AbstractCacheTest.php" );

/**
 * Test controller coming under configured privateCache / disableCache config
 */
class CacheStateModificationTest extends AbstractCacheTest {

    protected static $fixture_file = 'CacheStateModificationTest.yml';

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        CacheStatePrivatePage::class,
        CacheStateDisablePage::class,
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
        CacheHeaderConfiguration::config()->set('no_cache', null);
        CacheHeaderConfiguration::config()->set('no_store', null);

        // Configure controllers for CacheStateModificationExtension
        $controllers = [
            'disableCache' => [
                CacheStateDisablePageController::class
            ],
            'privateCache' => [
                CacheStatePrivatePageController::class
            ]
        ];

        CacheHeaderConfiguration::config()->set('controllers', $controllers);

    }

    public function testCacheStateModificationPrivate() {

        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        // Request for page with controller in privateCache configuration
        $response = $this->get("private-cache/");
        $body = $response->getBody();

        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);

        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PRIVATE), "Header {$headers['cache-control']} has private state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} has must-revalidate" );
    }

    public function testCacheStateModificationDisable() {

        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        // Request for page with controller in disableCache configuration
        $response = $this->get("disable-cache/");
        $body = $response->getBody();

        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "must have a cache-control response header");

        $parts = $this->getCacheControlParts($headers['cache-control']);

        $this->assertFalse( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_DISABLED), "Header {$headers['cache-control']} has disabled state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store") && $this->hasCacheDirective($parts, "no-cache"), "Header {$headers['cache-control']} has no-cache && no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} has must-revalidate" );
    }
}
