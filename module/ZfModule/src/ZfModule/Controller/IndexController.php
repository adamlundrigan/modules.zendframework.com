<?php

namespace ZfModule\Controller;

use Application\Service\RepositoryRetriever;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZfModule\Mapper;

class IndexController extends AbstractActionController
{
    /**
     * @var Mapper\Module
     */
    private $moduleMapper;

    /**
     * @var RepositoryRetriever
     */
    private $repositoryRetriever;

    /**
     * @param Mapper\Module $moduleMapper
     * @param RepositoryRetriever $repositoryRetriever
     */
    public function __construct(
        Mapper\Module $moduleMapper,
        RepositoryRetriever $repositoryRetriever
    ) {
        $this->moduleMapper = $moduleMapper;
        $this->repositoryRetriever = $repositoryRetriever;
    }

    public function viewAction()
    {
        $vendor = $this->params()->fromRoute('vendor', null);
        $module = $this->params()->fromRoute('module', null);

        $result = $this->moduleMapper->findByName($module);
        if (!$result) {
            return $this->notFoundAction();
        }

        $repository = $this->repositoryRetriever->getUserRepositoryMetadata($vendor, $module);
        if (!$repository) {
            return $this->notFoundAction();
        }

        $license = $this->repositoryRetriever->getRepositoryFileContent($vendor, $module, 'LICENSE');
        $composerConf = $this->repositoryRetriever->getRepositoryFileContent($vendor, $module, 'composer.json');

        /* HOTFIX for https://github.com/EvanDotPro/EdpGithub/issues/23 - markdown needs to be the last request */
        $readme = $this->repositoryRetriever->getRepositoryFileContent($vendor, $module, 'README.md', true);

        $viewModel = new ViewModel([
            'vendor' => $vendor,
            'module' => $module,
            'repository' => $repository,
            'readme' => $readme,
            'composerConf' => $composerConf,
            'license' => $license,
        ]);

        return $viewModel;
    }
}
