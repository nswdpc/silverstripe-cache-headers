<?php
namespace NSWDPC\Utilities\Cache;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Versioned\Versioned;

/**
 * Extension applied to ContentController
 */
class ContentControllerExtension extends Extension {

    /**
     * Handle actions after controller init
     */
    public function onAfterInit() {
        // When on the live stage, determine if the page has restrictions
        $stage = Versioned::get_stage();
        if($stage == Versioned::LIVE) {
            $record = $this->owner->data();
            if($record && ($record instanceof SiteTree) && !$this->hasAnyoneViewPermission($record)) {
                // If the page can't be viewed, disable cache
                HTTPCacheControlMiddleware::singleton()->disableCache(true)->useAppliedState();
            }
        }
    }

    /**
     * Determine whether a SiteTree record can be viewed by anyone, taking into
     * account site access settings and parent settings
     * @return bool
     */
    private function hasAnyoneViewPermission(SiteTree $record) : bool {

        if($record->CanViewType === InheritedPermissions::ANYONE) {
            // this record sets permissions
            return true;
        } else if ($record->CanViewType === InheritedPermissions::INHERIT) {
            // inheriting from parent or site config
            if( ($parent = $record->Parent()) && $parent->exists() ) {
                // record has parent
                return $this->hasAnyoneViewPermission($parent);
            } else {
                // inherit from site config
                $siteConfig = $record->getSiteConfig();
                return $siteConfig && $siteConfig->CanViewType === InheritedPermissions::ANYONE;
            }
        } else {
            // not
            return false;
        }
    }

}
