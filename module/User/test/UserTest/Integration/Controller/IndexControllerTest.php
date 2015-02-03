<?php

namespace UserTest\Integration\Controller;

use ApplicationTest\Integration\Util\Bootstrap;
use User\Entity\User as UserEntity;
use Zend\Http\Response as HttpResponse;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $viewHelperManager;

    protected function setUp()
    {
        parent::setUp();
        $this->setApplicationConfig(Bootstrap::getConfig());
        $this->getApplicationServiceLocator()->setAllowOverride(true);

        $mockTotalModules = $this->getMockBuilder('ZfModule\View\Helper\TotalModules')
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $mockListModule = $this->getMockBuilder('ZfModule\View\Helper\ListModule')
                               ->disableOriginalConstructor()
                               ->getMock();

        $this->viewHelperManager = $this->getApplicationServiceLocator()->get('ViewHelperManager');
        $this->viewHelperManager->setService('totalModules', $mockTotalModules);
        $this->viewHelperManager->setService('listModule', $mockListModule);

        $mockAuthService = $this->getMockBuilder('Zend\Authentication\AuthenticationService')
                                ->disableOriginalConstructor()
                                ->getMock();
        $mockAuthService->expects($this->any())
                        ->method('hasIdentity')
                        ->will($this->returnValue(true));
        $mockAuthService->expects($this->any())
                        ->method('getIdentity')
                        ->will($this->returnValue(new UserEntity()));

        $this->getApplicationServiceLocator()->setService('zfcuser_auth_service', $mockAuthService);
        $this->viewHelperManager->get('zfcUserIdentity')->setAuthService($mockAuthService);
    }

    public function testIndexActionCanBeAccessed()
    {
        $mockListModule = $this->viewHelperManager->get('listModule');
        $mockListModule->expects($this->once())
                       ->method('__invoke')
                       ->will($this->returnValue([]));

        $this->dispatch('/user');

        $this->assertControllerName('User\Controller\Index');
        $this->assertActionName('index');
        $this->assertTemplateName('user/index/index');
        $this->assertResponseStatusCode(HttpResponse::STATUS_CODE_200);
    }
}
