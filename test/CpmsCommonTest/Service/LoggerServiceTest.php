<?php

namespace CpmsCommonTest\Service;

use CpmsCommon\Service\ErrorCodeService;
use CpmsCommon\Service\LoggerService;
use CpmsCommonTest\Bootstrap;
use InvalidArgumentException;

/**
 * Class LoggerServiceTest
 *
 * @package CpmsCommonTest\Service
 */
class LoggerServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var  \Laminas\ServiceManager\ServiceManager */
    protected $serviceManager;

    public function setUp(): void
    {
        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();

        parent::setUp();
    }

    /**
     * Assert that the logger is an instance of LoggerService and can write to configured file.
     */
    public function testLoggerInstance(): void
    {
        /** @var array $config */
        $config = $this->serviceManager->get('config');
        /** @var LoggerService $logger */
        $logger = $this->serviceManager->get('Logger');

        $location = rtrim((string)($config['logger']['location'] ?? 'data/logs'), '/');
        $filename = (string)($config['logger']['filename'] ?? 'cpms-common.log');
        $logFile = $location . DIRECTORY_SEPARATOR . ltrim($filename, '/');

        $logger->info('logger service test message');
        $this->assertInstanceOf('CpmsCommon\Service\LoggerService', $logger);
        $this->assertTrue(file_exists($logFile));
    }

    public function testErrorCodeService(): void
    {
        /** @var ErrorCodeService $errorCodeService */
        $errorCodeService = $this->serviceManager->get('cpms\errorCodeService');
        $message = $errorCodeService->getErrorMessage(ErrorCodeService::INVALID_ACCESS_TOKEN);

        $this->assertTrue(is_array($message));
        $this->assertArrayHasKey('message', $message);
        $this->assertArrayHasKey('code', $message);

        $message = $errorCodeService->getErrorMessage(89098);

        $this->assertSame(ErrorCodeService::GENERIC_ERROR_CODE, $message['code']);
    }

    public function testLogException(): void
    {
        $prevException = new InvalidArgumentException('PhpUnit invalid exception');
        $exception = new \Exception('PHPUnit test exception', 78, $prevException);

        /** @var LoggerService $logger */
        $logger = $this->serviceManager->get('Logger');
        $done = $logger->logException($exception);

        $this->assertInstanceOf('CpmsCommon\Service\LoggerService', $done);
    }

}
