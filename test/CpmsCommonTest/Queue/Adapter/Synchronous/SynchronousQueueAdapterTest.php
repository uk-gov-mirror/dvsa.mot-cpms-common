<?php

namespace CpmsCommonTest\Queue\Adapter\Synchronous;

use CpmsCommon\Queue\Adapter\Synchronous\SynchronousQueueAdapter;
use CpmsCommon\Queue\JobInterface;
use CpmsCommon\Service\LoggerService;
use CpmsCommonTest\Bootstrap;
use CpmsCommon\Queue\Adapter\Synchronous\SynchronousQueueAdapterFactory;
use Laminas\ServiceManager\ServiceManager;
use Monolog\Handler\NoopHandler;
use Monolog\Logger as MonologLogger;

class SynchronousQueueAdapterTest extends \PHPUnit\Framework\TestCase
{
    private SynchronousQueueAdapter $adapter;

    public function setUp(): void
    {
        $this->adapter = new SynchronousQueueAdapter();
        $this->adapter->setServiceLocator(new ServiceManager());
        $logger = new LoggerService(new MonologLogger('test', [new NoopHandler()]));
        $this->adapter->setLogger($logger);
    }

    public function testCanCreateWithFactory(): void
    {
        $sl = Bootstrap::getInstance()->getServiceManager();
        $sl->get('cpms\queue\synchronous');
        $factory = new SynchronousQueueAdapterFactory();
        $instance = $factory->__invoke(new ServiceManager());
        $this->assertInstanceOf(SynchronousQueueAdapter::class, $instance);
    }

    public function testProcessesJobImmediately(): void
    {
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->expects($this->once())->method('handle');
        /** @var JobInterface $job */
        $this->adapter->enqueue($job);
    }

    public function testProcessesBulkJobsImmediately(): void
    {
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->expects($this->once())->method('handle');
        $job2 = $this->getMockBuilder(JobInterface::class)->getMock();
        $job2->expects($this->once())->method('handle');
        /** @var JobInterface $job */
        $this->adapter->enqueueAll([$job, $job2]);
    }

    public function testBulkProcessContinuesDespiteExceptions(): void
    {
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->expects($this->once())->method('handle')->willThrowException(new \Exception());
        $job2 = $this->getMockBuilder(JobInterface::class)->getMock();
        $job2->expects($this->once())->method('handle');
        /** @var JobInterface $job */
        $this->adapter->enqueueAll([$job, $job2]);
    }
}
