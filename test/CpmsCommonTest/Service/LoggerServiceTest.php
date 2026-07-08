<?php

namespace CpmsCommonTest\Service;

use CpmsCommon\Service\ErrorCodeService;
use CpmsCommon\Service\LoggerService;
use CpmsCommonTest\Bootstrap;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;

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
     * Assert that stream writer is an instance of Monolog\Handler\StreamHandler
     */
    public function testStreamWriterInstance(): void
    {
        $writer = $this->serviceManager->get('cpms\streamWriter');

        $this->assertInstanceOf(StreamHandler::class, $writer);
    }

    /**
     * Assert that the logger is an instance of Laminas\Log
     */
    public function testLoggerInstance(): void
    {
        /** @var array $config */
        $config = $this->serviceManager->get('config');
        $logger = $this->serviceManager->get('Logger');

        $logFile = $config['logger']['location'] . DIRECTORY_SEPARATOR . $config['logger']['filename'];

        $this->assertInstanceOf('CpmsCommon\Service\LoggerService', $logger);
        $this->assertTrue(file_exists($logFile));
    }

    /**
     */
    public function testSetReplacement(): void
    {
        /** @var LoggerService $logger */
        $logger = $this->serviceManager->get('Logger');

        $logger->setLogData(null);
        $replacements = $logger->getLogData();

        $this->assertNotNull($replacements);
        $this->assertInstanceOf('CpmsCommon\Log\LogData', $replacements);
    }

    public function testAddReplacement(): void
    {
        $key   = 'data';
        $value = 'test value';

        /** @var LoggerService $logger */
        $logger = $this->serviceManager->get('Logger');

        $logger->addReplacement($key, $value);

        $this->assertSame($logger->getLogData()->getData(), $value);
    }

    public function testGetReplacementException(): void
    {
        $this->expectException(\Laminas\Stdlib\Exception\BadMethodCallException::class);

        $key = 'testKey';

        /** @var LoggerService $logger */
        $logger = $this->serviceManager->get('Logger');

        /** @phpstan-ignore property.notFound, expr.resultUnused */
        $logger->getLogData()->{$key};
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

    public function testProcessException(): void
    {
        $prevException = new InvalidArgumentException('PhpUnit invalid exception');
        $exception = new \Exception('PHPUnit test exception', 78, $prevException);

        /** @var LoggerService $logger */
        $logger = $this->serviceManager->get('Logger');
        $done = $logger->processException($exception);

        $this->assertNotEmpty($done);
    }
}
