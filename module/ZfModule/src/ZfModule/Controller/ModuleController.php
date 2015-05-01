<?php

namespace ZfModule\Controller;

use Application\Service\RepositoryRetriever;
use EdpGithub\Collection\RepositoryCollection;
use stdClass;
use Zend\Http;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZfcUser\Controller\Plugin;
use ZfModule\Mapper;
use ZfModule\Service;

/**
 * @method Http\Request getRequest()
 * @method Plugin\ZfcUserAuthentication zfcUserAuthentication()
 */
class ModuleController extends AbstractActionController
{
    /**
     * @var Mapper\Module
     */
    private $moduleMapper;

    /**
     * @var Service\Module
     */
    private $moduleService;

    /**
     * @var RepositoryRetriever
     */
    private $repositoryRetriever;

    /**
     * @param Mapper\Module $moduleMapper
     * @param Service\Module $moduleService
     * @param RepositoryRetriever $repositoryRetriever
     */
    public function __construct(
        Mapper\Module $moduleMapper,
        Service\Module $moduleService,
        RepositoryRetriever $repositoryRetriever
    ) {
        $this->moduleMapper = $moduleMapper;
        $this->moduleService = $moduleService;
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
        $readme = $this->repositoryRetriever->getRepositoryFileContent($vendor, $module, 'README.md', true);

        return new ViewModel([
            'vendor' => $vendor,
            'module' => $module,
            'repository' => $repository,
            'readme' => $readme,
            'composerConf' => $composerConf,
            'license' => $license,
        ]);
    }

    public function indexAction()
    {
        if (!$this->zfcUserAuthentication()->hasIdentity()) {
            return $this->redirect()->toRoute('zfcuser/login');
        }

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);

        $currentUserRepositories = $this->repositoryRetriever->getAuthenticatedUserRepositories([
            'type' => 'all',
            'per_page' => 100,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);
        if ($currentUserRepositories === false) {
            $this->getResponse()->setStatusCode(503);
            $viewModel->setVariable('errorMessage', 'module_fetch_failed');

            return $viewModel;
        }

        $repositories = $this->unregisteredRepositories($currentUserRepositories);
        $viewModel->setVariable('repositories', $repositories);

        return $viewModel;
    }

    public function listAction()
    {
        if (!$this->zfcUserAuthentication()->hasIdentity()) {
            return $this->redirect()->toRoute('zfcuser/login');
        }

        $owner = $this->params()->fromRoute('owner', null);

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setTemplate('zf-module/module/index.phtml');

        $userRepositories = $this->repositoryRetriever->getUserRepositories($owner, [
            'per_page' => 100,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);
        if ($userRepositories === false) {
            $this->getResponse()->setStatusCode(503);
            $viewModel->setVariable('errorMessage', 'module_fetch_failed');

            return $viewModel;
        }

        $repositories = $this->unregisteredRepositories($userRepositories);
        $viewModel->setVariable('repositories', $repositories);

        return $viewModel;
    }

    /**
     * @param RepositoryCollection $repositories
     * @return stdClass[]
     */
    private function unregisteredRepositories(RepositoryCollection $repositories)
    {
        return array_filter(iterator_to_array($repositories), function ($repository) {
            if ($repository->fork) {
                return false;
            }

            if (!$repository->permissions->push) {
                return false;
            }

            if ($this->moduleMapper->findByName($repository->name)) {
                return false;
            }

            return true;
        });
    }

    /**
     * Register a new Module
     *
     * @return Http\Response
     * @throws Exception\InvalidDataException
     * @throws Exception\RepositoryException
     */
    public function addAction()
    {
        if (!$this->zfcUserAuthentication()->hasIdentity()) {
            return $this->redirect()->toRoute('zfcuser/login');
        }

        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw Exception\InvalidDataException::fromInvalidRequest(
                'Something went wrong with the post values of the request...',
                $this->getRequest()
            );
        }

        $postParams = $request->getPost();

        $repo = $postParams->get('repo');
        $owner  = $postParams->get('owner');

        $repository = $this->repositoryRetriever->getUserRepositoryMetadata($owner, $repo);

        if (!($repository instanceof \stdClass)) {
            throw Exception\RepositoryException::fromNotFoundRepository(
                'Not able to fetch the repository from GitHub due to an unknown error.',
                $owner,
                $repo
            );
        }

        if ($repository->fork || !$repository->permissions->push) {
            throw Exception\RepositoryException::fromInsufficientPermissions(
                'You have no permission to add this module. The reason might be that you are neither the owner nor a collaborator of this repository.',
                $repository->full_name,
                ['pushAccess', 'noFork']
            );
        }

        if (!$this->moduleService->isModule($repository)) {
            throw Exception\RepositoryException::fromNonModuleRepository(
                $repository->name . ' is not a Zend Framework Module',
                $repository->full_name
            );
        }

        $module = $this->moduleService->register($repository);
        $this->flashMessenger()->addSuccessMessage($module->getName() . ' has been added to ZF Modules');

        return $this->redirect()->toRoute('zfcuser');
    }

    /**
     * Removes a Module
     *
     * @return Http\Response
     * @throws Exception\InvalidDataException
     * @throws Exception\RepositoryException
     */
    public function removeAction()
    {
        if (!$this->zfcUserAuthentication()->hasIdentity()) {
            return $this->redirect()->toRoute('zfcuser/login');
        }

        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw Exception\InvalidDataException::fromInvalidRequest(
                'Something went wrong with the post values of the request...',
                $request
            );
        }

        $postParams = $request->getPost();

        $repo = $postParams->get('repo');
        $owner  = $postParams->get('owner');

        $repository = $this->repositoryRetriever->getUserRepositoryMetadata($owner, $repo);

        if (!$repository instanceof \stdClass) {
            throw Exception\RepositoryException::fromNotFoundRepository(
                'Not able to fetch the repository from GitHub due to an unknown error.',
                $owner,
                $repo
            );
        }

        if ($repository->fork || !$repository->permissions->push) {
            throw Exception\RepositoryException::fromInsufficientPermissions(
                'You have no permission to remove this module. The reason might be that you are neither the owner nor a collaborator of this repository.',
                $repository->full_name,
                ['pushAccess', 'noFork']
            );
        }

        $module = $this->moduleMapper->findByUrl($repository->html_url);

        if (!$module) {
            throw Exception\RepositoryException::fromNotFoundRepositoryUrl(
                $repository->name . ' was not found',
                $repository->html_url
            );
        }

        $this->moduleMapper->delete($module);
        $this->flashMessenger()->addSuccessMessage($repository->name . ' has been removed from ZF Modules');

        return $this->redirect()->toRoute('zfcuser');
    }
}
