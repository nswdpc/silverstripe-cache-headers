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

    protected function setUp()
    {
        parent::setUp();

        // The test application has a configured public state by default
        CacheHeaderConfiguration::config()->set('state', 'public');
        CacheHeaderConfiguration::config()->set('max_age', $this->maxAge);
        CacheHeaderConfiguration::config()->set('s_max_age', $this->sMaxAge);
        CacheHeaderConfiguration::config()->set('must_revalidate', true);

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
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);

        $this->assertTrue( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PRIVATE), "Header {$headers['cache-control']} has private state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
    }

    public function testCacheStateModiicationDisable() {

        $this->setSiteConfigCanViewType( InheritedPermissions::ANYONE );

        // Request for page with controller in disableCache configuration
        $response = $this->get("disable-cache/");
        $body = $response->getBody();

        $headers = $response->getHeaders();
        $this->assertTrue(!empty($headers['cache-control']), "no cache-control header in response");

        $parts = $this->getCacheControlParts($headers['cache-control']);

        $this->assertFalse( $this->hasCachingState($parts, HTTPCacheControlMiddleware::STATE_PUBLIC), "Header {$headers['cache-control']} does not have public state" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-store"), "Header {$headers['cache-control']} missing no-store" );
        $this->assertTrue( $this->hasCacheDirective($parts, "no-cache"), "Header {$headers['cache-control']} missing no-cache" );
        $this->assertTrue( $this->hasCacheDirective($parts, "must-revalidate"), "Header {$headers['cache-control']} missing must-revalidate" );
    }
}
