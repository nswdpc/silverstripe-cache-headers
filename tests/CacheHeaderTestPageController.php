<?php

namespace NSWDPC\Utilities\Cache\Tests;

use NSWDPC\Utilities\Cache\CacheHeaderConfiguration;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use PageController;

class CacheHeaderTestPageController extends PageController implements TestOnly {

    public function doInit() {

        // set public cache by default, as that is what we want to test
        $inst = Injector::inst()->create(CacheHeaderConfiguration::class);
        $inst->config()->set('state', 'public');
        $inst->config()->set('max_age', 7200);
        $inst->config()->set('s_max_age', 7201);
        $inst->config()->set('must_revalidate', true);

        parent::doInit();
    }

    public function Form()
    {

        if($this->request->getVar('noform')) {
            return;
        }

        $form = Form::create(
            $this,
            'CacheHeaderTestForm',
            FieldList::create([ TextField::create('Title') ]),
            FieldList::create([ FormAction::create('submit', 'Submit') ])
        );

        if($this->request->getVar('formcache')) {
            $form->disableSecurityToken();
            $form->setFormMethod('GET', false);
        }

        return $form;
    }

}
