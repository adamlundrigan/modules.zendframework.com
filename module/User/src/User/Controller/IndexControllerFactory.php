<?php

namespace User\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfModule\Mapper\Module as ModuleMapper;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $controllerManager
     * @return IndexController
     */
    public function createService(ServiceLocatorInterface $controllerManager)
    {
        /* @var ServiceLocatorInterface $controllerManager */
        $serviceManager = $controllerManager->getServiceLocator();

        /* @var ModuleMapper $moduleMapper */
        $moduleMapper = $serviceManager->get('zfmodule_mapper_module');

        return new IndexController(
            $moduleMapper
        );
    }
}
