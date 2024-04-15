<?php
namespace NSWDPC\Utilities\Cache;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;

/**
 * Extension applied to ContentController
 */
class ContentControllerExtension extends Extension {

    /**
     * Handle actions after controller init
     */
    public function onAfterInit() {
        $this->applyRestrictedRecordCacheState();
    }

    /**
     * When on the live stage, determine if the page has restrictions
     * The draft stage defines its own cache state
     */
    private function applyRestrictedRecordCacheState() {
        $stage = Versioned::get_stage();
        if($stage != Versioned::LIVE) {
            return;
        }
        $record = $this->owner->data();
        if(!$record || !($record instanceof SiteTree)) {
            // misconfiguration, disable cache
            Logger::log("Controller has no record, calling disabledCache()", "NOTICE");
            return $this->setDisableCacheState();
        }
        $siteConfig = $record->getSiteConfig();
        if(!$siteConfig || !($siteConfig instanceof SiteConfig)) {
            // misconfiguration, disable cache
            Logger::log("Record is not a Siteconfig, calling disabledCache()", "NOTICE");
            return $this->setDisableCacheState();
        }
        if($siteConfig->CanViewType !== InheritedPermissions::ANYONE) {
            // restricted siteconfig setting
            return $this->setDisableCacheState();
        } else if(!$this->hasAnyoneViewPermission($record, $siteConfig)) {
            // restricted sitetree record setting
            return $this->setDisableCacheState();
        }
    }

    /**
     * Disable the cache state, by calling disableCache
     * and ensure that the state applied here is used
     */
    private function setDisableCacheState() {
        HTTPCacheControlMiddleware::singleton()->disableCache(true)->useAppliedState();
        return;
    }

    /**
     * Determine whether a SiteTree record can be viewed by anyone, taking into
     * account parent settings and site config
     * @return bool
     */
    private function hasAnyoneViewPermission(SiteTree $record, SiteConfig $siteConfig) : bool {
        if($record->CanViewType === InheritedPermissions::ANYONE) {
            // this record sets permissions
            return true;
        } else if ($record->CanViewType === InheritedPermissions::INHERIT) {
            if( ($parent = $record->Parent()) && $parent->exists() ) {
                // inheriting from parent
                return $this->hasAnyoneViewPermission($parent, $siteConfig);
            } else {
                // record has no parent, site config check will pick this up
                return $siteConfig->CanViewType === InheritedPermissions::ANYONE;
            }
        } else {
            // not
            return false;
        }
    }

}
