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
     * Return whether a SiteTree record has CanViewType of ANYONE
     * @return bool
     */
    private function hasAnyoneViewPermission(SiteTree $record) : bool {

        $siteConfig = $record->getSiteConfig();
        if($siteConfig->CanViewType !== InheritedPermissions::ANYONE) {
            return false;
        }

        if($record->CanViewType === InheritedPermissions::ANYONE) {
            // this record sets permissions
            return true;
        } else if ($record->CanViewType === InheritedPermissions::INHERIT) {
            // inherit permissions from parent
            $parent = $record->Parent();
            if(!empty($parent->ID)) {
                // find permissions from parent
                return $this->hasAnyoneViewPermission($parent);
            } else if($siteConfig->CanViewType ===  InheritedPermissions::ANYONE) {
                // SiteConfig sets 'Anyone' permission
                return true;
            }
        }
        return false;
    }

}
