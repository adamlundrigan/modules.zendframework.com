<?php

namespace ZfModule\Controller;

use Application\Service\RepositoryRetriever;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfModule\Mapper;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ServiceLocatorInterface $controllerManager
     * @return IndexController
     */
    public function createService(ServiceLocatorInterface $controllerManager)
    {
        /* @var ControllerManager $controllerManager */
        $serviceManager = $controllerManager->getServiceLocator();

        /* @var Mapper\Module $moduleMapper */
        $moduleMapper = $serviceManager->get('zfmodule_mapper_module');

        /* @var RepositoryRetriever $repositoryRetriever */
        $repositoryRetriever = $serviceManager->get(RepositoryRetriever::class);

        return new IndexController(
            $moduleMapper,
            $repositoryRetriever
        );
    }
}
