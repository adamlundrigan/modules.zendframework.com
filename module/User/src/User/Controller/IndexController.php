<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZfModule\Mapper\Module as ModuleMapper;

class IndexController extends AbstractActionController
{
    /**
     * @var ModuleMapper
     */
    protected $moduleMapper;

    /**
     * @param ModuleMapper $mapper
     */
    public function __construct(ModuleMapper $mapper)
    {
        $this->moduleMapper = $mapper;
    }

    public function indexAction()
    {
        $registeredModules = $this->renderModuleListAction();
        $registeredModules->setTerminal(false);

        $vm = new ViewModel();
        $vm->addChild($registeredModules, 'registered_modules');

        return $vm;
    }

    public function renderModuleListAction()
    {
        $modules = $this->moduleMapper->findByOwner($this->zfcUserAuthentication()->getIdentity()->getId());

        $vm = new ViewModel();
        $vm->setTemplate('user/index/render-module-list');
        $vm->setVariable('modules', $modules);
        $vm->setTerminal(true);

        return $vm;
    }
}
