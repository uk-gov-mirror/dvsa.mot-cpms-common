<?php

namespace CpmsCommonTest\Service;

use CpmsCommon\Service\Config\ServiceOptions;
use CpmsCommon\Service\ErrorCodeService;
use CpmsCommon\Utility\Util;
use CpmsCommonTest\Bootstrap;
use CpmsCommonTest\SampleService;
use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\ServiceManager;
use Monolog\Level;

class SampleServiceTest extends \PHPUnit\Framework\TestCase
{
    protected SampleService $service;

    protected ServiceManager $serviceManager;

    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        $this->service = new SampleService();
        $this->service->setOptions(new ServiceOptions());

        $this->service->setServiceLocator($this->serviceManager);
    }

    public function testServiceInstance(): void
    {
        $this->assertInstanceOf('CpmsCommon\AbstractService', $this->service);
        $this->assertInstanceOf(MotLogger::class, $this->service->getLogger());
        $this->assertInstanceOf('Laminas\ServiceManager\ServiceManager', $this->service->getServiceLocator());
        $this->assertInstanceOf('CpmsCommon\Service\Config\ServiceOptions', $this->service->getOptions());

        $message = $this->service->getErrorMessage(ErrorCodeService::AN_ERROR_OCCURRED);
        $this->assertTrue(is_array($message));
        $this->assertArrayHasKey('code', $message);

        $this->service->logException(new \Exception('phpunit'));
        $this->service->log('phpunit', Level::Debug);

        /** @var array $config */
        $config = $this->serviceManager->get('config');
        $logLocation = $config['logger']['location'];
        Util::deleteDir($logLocation);
    }

    public function testServiceMethod(): void
    {
        $model = $this->service->getModel('test');
        $this->assertSame('TEST-MODEL', $model);

        $required = array('one', 'two', 'three');
        $data = array(
            'one'   => 1,
            'two'   => 2,
            'three' => 3,
            'four'  => 4
        );

        $results = $this->service->getParams($data, $required);
        foreach ($required as $field) {
            $this->assertArrayHasKey($field, $results['params']);
        }

        unset($data['two']);
        $results = $this->service->getParams($data, $required);
        $this->assertArrayHasKey('code', $results);
        $this->assertArrayHasKey('http_status_code', $results);
        $this->assertArrayHasKey('message', $results);
        $this->assertSame(400, $results['http_status_code']);
    }

    public function testPositiveAmount(): void
    {
        $done = $this->service->validPositiveAmount(-90.89);
        $this->assertFalse($done);

        $done = $this->service->validPositiveAmount(2390.89);
        $this->assertTrue($done);
    }
}
