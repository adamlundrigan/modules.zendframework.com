<?php

use EdpGithub\Client;
use ZfModule\Controller;
use ZfModule\Delegators\EdpGithubClientAuthenticator;
use ZfModule\Mapper\ModuleHydrator;
use ZfModule\View\Helper;

return [
    'controllers'  => [
        'factories' => [
            Controller\IndexController::class => Controller\IndexControllerFactory::class,
        ],
    ],
    'router'       => [
        'routes' => [
            'view-module' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/:vendor/:module',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'view',
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'zf-module' => __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'factories'  => [
            'listModule'   => Helper\ListModuleFactory::class,
            'newModule'    => Helper\NewModuleFactory::class,
            'totalModules' => Helper\TotalModulesFactory::class,
        ],
        'invokables' => [
            'moduleView'        => Helper\ModuleView::class,
            'moduleDescription' => Helper\ModuleDescription::class,
            'composerView'      => Helper\ComposerView::class,
        ],
    ],
    'service_manager' => [
        'invokables' => [
            ModuleHydrator::class => ModuleHydrator::class,
        ],
        'factories' => [
            'zfmodule_service_module' => ZfModule\Service\ModuleFactory::class,
            'zfmodule_mapper_module' => ZfModule\Mapper\ModuleFactory::class,
        ],
        'delegators' => [
            Client::class => [
                EdpGithubClientAuthenticator::class,
            ],
        ],
    ],
];
