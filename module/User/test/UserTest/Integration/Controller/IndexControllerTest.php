<?php

namespace UserTest\Integration\Controller;

use ApplicationTest\Integration\Util\Bootstrap;
use User\Entity\User as UserEntity;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Http\Response as HttpResponse;
use Zend\Stdlib\Hydrator\ClassMethods;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use ZfModule\Entity\Module as ModuleEntity;
use ZfModule\Mapper\Module as ModuleMapper;
use ZfModule\View\Helper\TotalModules;

class IndexControllerTest extends AbstractHttpControllerTestCase
{
    protected $viewHelperManager;

    protected function setUp()
    {
        parent::setUp();
        $this->setApplicationConfig(Bootstrap::getConfig());
        $this->getApplicationServiceLocator()->setAllowOverride(true);

        $mockTotalModules = $this->getMockBuilder(TotalModules::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $this->viewHelperManager = $this->getApplicationServiceLocator()->get('ViewHelperManager');
        $this->viewHelperManager->setService('totalModules', $mockTotalModules);

        $mockAuthService = $this->getMockBuilder(AuthenticationService::class)
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

        $mockMapper = $this->getMockBuilder(ModuleMapper::class)
                           ->disableOriginalConstructor()
                           ->getMock();
        $this->getApplicationServiceLocator()->setService('zfmodule_mapper_module', $mockMapper);
    }

    /**
     * @group integration
     */
    public function testIndexActionRendersUserModuleList()
    {
        $mockResultSet = new HydratingResultSet(new ClassMethods(false), new ModuleEntity());
        $mockResultSet->initialize([[
            'id' => 123,
            'name' => 'FooModule',
            'description' => 'some random module',
            'url' => 'https://github.com/zendframework/modules.zendframework.com',
        ]]);

        $mockMapper = $this->getApplicationServiceLocator()->get('zfmodule_mapper_module');
        $mockMapper->expects($this->once())
                   ->method('findByOwner')
                   ->willReturn($mockResultSet);

        $this->dispatch('/user');

        $this->assertControllerName('User\Controller\Index');
        $this->assertActionName('index');
        $this->assertTemplateName('user/index/index');
        $this->assertResponseStatusCode(HttpResponse::STATUS_CODE_200);
        $this->assertContains('FooModule', $this->getResponse()->getContent());
        $this->assertContains('/user/module/123/remove', $this->getResponse()->getContent());
    }
}
