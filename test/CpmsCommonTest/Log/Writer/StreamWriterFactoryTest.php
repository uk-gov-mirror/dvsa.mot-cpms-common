<?php

namespace CpmsCommonTest\Log\Writer;

use CpmsCommon\Log\Writer\StreamWriterFactory;
use CpmsCommon\Utility\Util;
use CpmsCommonTest\Bootstrap;
use Monolog\Handler\StreamHandler;
use Laminas\ServiceManager\ServiceManager;

/**
 * Class StreamWriterFactoryTest
 *
 * @package CpmsCommonTest\Log\Writer
 */
class StreamWriterFactoryTest extends \PHPUnit\Framework\TestCase
{
    private StreamWriterFactory $writerFactory;

    public function setUp(): void
    {
        $this->writerFactory = new StreamWriterFactory();
    }

    public function testCreateService(): void
    {
        $serviceManager = $this->getServiceManager();
        $result         = $this->writerFactory->__invoke($serviceManager, null);

        $this->assertInstanceOf(StreamHandler::class, $result);
    }

    public function testCreateServiceWithNoFileName(): void
    {
        $config['logger']['filename']  = null;
        $config['logger']['location']  = null;
        $config['logger']['mode']      = 0755;
        $config['logger']['priority']  = '';
        $config['logger']['separator'] = '';
        $serviceManager                = $this->getServiceManager($config);
        /** @var StreamHandler $result */
        $result = $this->writerFactory->__invoke($serviceManager, null);
        $this->assertInstanceOf(StreamHandler::class, $result);
    }

    public function testCreateLogDir(): void
    {
        $config['logger']['filename']    = null;
        $config['logger']['location']    = sys_get_temp_dir() . '/log';
        $config['logger']['mode']        = 0755;
        $config['logger']['priority']    = '';
        $config['logger']['separator']   = '';
        $config['logger']['replacement'] = '';
        $serviceManager                  = $this->getServiceManager($config);

        if (file_exists($config['logger']['location'])) {
            Util::deleteDir($config['logger']['location']);
        }
        /** @var StreamHandler $result */
        $result = $this->writerFactory->__invoke($serviceManager, null);
        $this->assertInstanceOf(StreamHandler::class, $result);
    }

    private function getServiceManager(array $config = null): ServiceManager
    {
        $serviceManager = Bootstrap::getInstance()->getServiceManager();

        if (!empty($config)) {
            /** @var array $managerConfig */
            $managerConfig = $serviceManager->get('config');
            $config = array_merge($managerConfig, $config);

            $serviceManager->setAllowOverride(true);
            $serviceManager->setService('config', $config);
        }

        return $serviceManager;
    }
}
