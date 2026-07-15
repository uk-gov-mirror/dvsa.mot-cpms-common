<?php

namespace CpmsCommonTest\Queue\Adapter\Synchronous;

use CpmsCommon\Queue\Adapter\Synchronous\SynchronousQueueAdapter;
use CpmsCommon\Queue\JobInterface;
use CpmsCommonTest\Bootstrap;
use CpmsCommon\Queue\Adapter\Synchronous\SynchronousQueueAdapterFactory;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Laminas\ServiceManager\ServiceManager;

class SynchronousQueueAdapterTest extends \PHPUnit\Framework\TestCase
{
    private SynchronousQueueAdapter $adapter;
    private TestHandler $testHandler;

    public function setUp(): void
    {
        $this->adapter = new SynchronousQueueAdapter();
        $this->adapter->setServiceLocator(new ServiceManager());

        $this->testHandler = new TestHandler(Level::Debug);
        $logger = new Logger('queue-test');
        $logger->pushHandler($this->testHandler);
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
        $job->expects($this->once())->method('handle')->willReturn(true);
        /** @var JobInterface $job */
        $this->adapter->enqueue($job);

        $startedRecord = $this->findRecord(Level::Debug, 'Queue read started.');
        $finishedRecord = $this->findRecord(Level::Debug, 'Queue read finished.');

        $this->assertNotNull($startedRecord);
        $this->assertNotNull($finishedRecord);
        $this->assertSame(['message_count' => 1], $startedRecord->context);
        $this->assertSame([
            'message_count' => 1,
            'processed_count' => 1,
            'success_count' => 1,
            'failed_count' => 0,
        ], $finishedRecord->context);
    }

    public function testProcessesBulkJobsImmediately(): void
    {
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->expects($this->once())->method('handle')->willReturn(true);
        $job2 = $this->getMockBuilder(JobInterface::class)->getMock();
        $job2->expects($this->once())->method('handle')->willReturn(true);
        /** @var JobInterface $job */
        $this->adapter->enqueueAll([$job, $job2]);

        $startedRecord = $this->findRecord(Level::Debug, 'Queue read started.');
        $finishedRecord = $this->findRecord(Level::Debug, 'Queue read finished.');

        $this->assertNotNull($startedRecord);
        $this->assertNotNull($finishedRecord);
        $this->assertSame(['message_count' => 2], $startedRecord->context);
        $this->assertSame([
            'message_count' => 2,
            'processed_count' => 2,
            'success_count' => 2,
            'failed_count' => 0,
        ], $finishedRecord->context);
    }

    public function testBulkProcessContinuesDespiteExceptions(): void
    {
        $exception = new \Exception('Bulk queue job failure');
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->expects($this->once())->method('handle')->willThrowException($exception);
        $job2 = $this->getMockBuilder(JobInterface::class)->getMock();
        $job2->expects($this->once())->method('handle')->willReturn(true);
        /** @var JobInterface $job */
        $this->adapter->enqueueAll([$job, $job2]);

        $warningRecord = $this->findRecord(Level::Warning, 'Queue acknowledgement contract issue: job threw during acknowledgement.');
        $errorRecord = $this->findRecord(Level::Error, 'Bulk queue job failure');
        $finishedRecord = $this->findRecord(Level::Debug, 'Queue read finished.');

        $this->assertNotNull($warningRecord);
        $this->assertNotNull($errorRecord);
        $this->assertNotNull($finishedRecord);
        $this->assertSame([
            'message_count' => 2,
            'processed_count' => 2,
            'success_count' => 1,
            'failed_count' => 1,
        ], $finishedRecord->context);
    }

    public function testLogsQueueReadStartAndFinishWithCounts(): void
    {
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->method('handle')->willReturn(true);

        $this->adapter->enqueueAll([$job, $job]);

        $debugRecords = array_filter($this->testHandler->getRecords(), static function ($record): bool {
            return $record->level === Level::Debug;
        });

        $this->assertCount(2, $debugRecords);
    }

    public function testLogsWarningWhenAckContractIsNotMet(): void
    {
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->method('handle')->willReturn(false);

        $this->adapter->enqueue($job);

        $warningRecord = $this->findRecord(Level::Warning, 'Queue acknowledgement contract issue: job did not acknowledge processing.');
        $finishedRecord = $this->findRecord(Level::Debug, 'Queue read finished.');

        $this->assertNotNull($warningRecord);
        $this->assertNotNull($finishedRecord);
        $this->assertSame([
            'message_count' => 1,
            'processed_count' => 1,
            'success_count' => 0,
            'failed_count' => 1,
        ], $finishedRecord->context);
    }

    public function testExceptionPathLogsWarningErrorAndFailureCounts(): void
    {
        $exception = new \Exception('Queue job failure');
        $job = $this->getMockBuilder(JobInterface::class)->getMock();
        $job->expects($this->once())->method('handle')->willThrowException($exception);

        $this->adapter->enqueue($job);

        $warningRecord = $this->findRecord(Level::Warning, 'Queue acknowledgement contract issue: job threw during acknowledgement.');
        $errorRecord = $this->findRecord(Level::Error, 'Queue job failure');
        $finishedRecord = $this->findRecord(Level::Debug, 'Queue read finished.');

        $this->assertNotNull($warningRecord);
        $this->assertNotNull($errorRecord);
        $this->assertNotNull($finishedRecord);
        $this->assertSame([
            'message_count' => 1,
            'processed_count' => 1,
            'success_count' => 0,
            'failed_count' => 1,
        ], $finishedRecord->context);
    }

    private function findRecord(Level $level, string $message): ?object
    {
        foreach ($this->testHandler->getRecords() as $record) {
            if ($record->level === $level && $record->message === $message) {
                return $record;
            }
        }

        return null;
    }
}
