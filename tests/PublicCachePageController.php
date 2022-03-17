<?php

namespace NSWDPC\Utilities\Cache\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use PageController;

class PublicCachePageController extends PageController implements TestOnly {

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
