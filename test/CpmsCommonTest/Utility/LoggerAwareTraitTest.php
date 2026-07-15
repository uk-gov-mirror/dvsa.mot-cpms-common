<?php

namespace CpmsCommonTest\Utility;

use CpmsCommonTest\Bootstrap;
use Laminas\ServiceManager\ServiceManager;
use CpmsCommonTest\Mock\LoggerAwareTraitMock;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerAwareTraitTest extends TestCase
{
    private LoggerAwareTraitMock $trait;

    public function testLog(): void
    {
        $this->setUpTrait($this->getServiceLocator());

        $this->trait->log('message');

        $this->assertInstanceOf(LoggerInterface::class, $this->trait->getLogger());
    }

    public function testLogException(): void
    {
        $this->setUpTrait($this->getServiceLocator());

        $result = $this->trait->logException(new \Exception('Exception'));

        $this->assertInstanceOf(LoggerInterface::class, $result);
    }

    public function testUsesConfiguredLoggerAliasWhenServiceExists(): void
    {
        $defaultLogger = new Logger('default');
        $defaultLogger->pushHandler(new NullHandler());

        $customLogger = new Logger('custom');
        $customLogger->pushHandler(new NullHandler());

        $this->setUpTrait(new ServiceManager([
            'services' => [
                'config' => [
                    'cpms_api' => [
                        'logger_alias' => 'custom\\logger',
                    ],
                ],
                'cpms\\client\\logger' => $defaultLogger,
                'custom\\logger' => $customLogger,
            ],
        ]));

        $this->assertSame($customLogger, $this->trait->getLogger());
    }

    public function testFallsBackToDefaultAliasWhenConfiguredAliasIsInvalid(): void
    {
        $defaultLogger = new Logger('default');
        $defaultLogger->pushHandler(new NullHandler());

        $this->setUpTrait(new ServiceManager([
            'services' => [
                'config' => [
                    'cpms_api' => [
                        'logger_alias' => 'invalid\\logger',
                    ],
                ],
                'cpms\\client\\logger' => $defaultLogger,
            ],
        ]));

        $this->assertSame($defaultLogger, $this->trait->getLogger());
    }

    private function setUpTrait(ServiceManager $serviceManager): void
    {
        $this->trait = new LoggerAwareTraitMock();
        $this->trait->setServiceLocator($serviceManager);
    }

    private function getServiceLocator(): ServiceManager
    {
        return Bootstrap::getInstance()->getServiceManager();
    }
}
