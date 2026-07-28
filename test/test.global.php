<?php

use CpmsCommon\Log\LogDataProviderFactory;
use CpmsCommon\Service\Config\AuthServiceOptions;
use CpmsCommon\Utility\TokenGenerator;
use CpmsCommonTest\Mock\MockAuthService;

return array(
    'display_exception' => true,
    'router' => array(
        'routes' => array(
            'cpms-common-home' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/cpms-common-test[/:id]',
                    'defaults' => array(
                        'controller' => 'CpmsCommonTest\Sample',
                    )
                ),
            ),
            'cpms-test'        => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/test-index',
                    'defaults' => array(
                        'controller' => 'CpmsCommonTest\Sample',
                        'action'     => 'index'
                    )
                ),
            ),
        ),
    ),
    'controllers'       => array(
        'invokables' => array(
            'CpmsCommonTest\Sample' => \CpmsCommonTest\SampleController::class,

        ),
    ),
    'view_manager'      => array(
        'not_found_template'  => 'error/404',
        'exception_template'  => 'error/index',
        'template_map'        => array(
            'layout/layout' => __DIR__ . '/view/layout/layout.phtml',
            'error/404'     => __DIR__ . '/view/error/404.phtml',
            'error/index'   => __DIR__ . '/view/error/index.phtml',
            'sample/index'  => __DIR__ . '/view/cpms-common/index/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/view',
        ),
    ),
    'service_manager'   => array(
        'factories' => array(
            'cpms\model\test'                          => function () {
                return 'TEST-MODEL';
            },
            'Logger'                                  => 'CpmsCommon\Service\LoggerServiceFactory',
            LogDataProviderFactory::AUTH_SERVICE_ALIAS => function () {
                $testData = array(
                    'accessToken' => TokenGenerator::create(),
                    'user'        => 2345,
                );
                $options  = new AuthServiceOptions();
                $options->setFromArray($testData);
                $authService = new MockAuthService();
                $authService->setOptions($options);

                return $authService;
            },
            'cpms/errorCodeService'          => 'CpmsCommon\Service\ErrorCodeServiceFactory',
        ),
        'shared'    => array(
            'cpms\api\contentType' => false
        )
    ),
    'logger'            => array(
        'priority'       => \LOG_DEBUG,
        'location'       => 'data/logs', // log location
        // 'replacement' => 'test\replacements',
        'formatter'      => 'dvsa\formatter',
        'separator'      => '^^*',
        'replacement'    => 'logger\data\provider',
        'dateTimeFormat' => 'Y-m-d H:i:s.u',
        'writers'        => []
    ),
);
