<?php

namespace CpmsCommon\Queue\Adapter\Synchronous;

use CpmsCommon\Queue\JobInterface;
use CpmsCommon\Queue\QueueInterface;
use CpmsCommon\Utility\LoggerAwareTrait;
use Laminas\ServiceManager\ServiceLocatorInterface;
use CpmsCommon\Utility\LoggerAwareInterface;

class SynchronousQueueAdapter implements QueueInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    // This is an anti-pattern added here to make PoC zf2->zf3 migration happen. Sorry. This should be fixed in the future!
    private ServiceLocatorInterface $serviceLocator;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator($serviceLocator): SynchronousQueueAdapter
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * @param JobInterface $job
     */
    public function enqueue(JobInterface $job): void
    {
        $this->getLogger()->debug('Queue read started.', ['message_count' => 1]);
        $processed = $this->process($job);
        $this->getLogger()->debug('Queue read finished.', [
            'message_count' => 1,
            'processed_count' => 1,
            'success_count' => $processed ? 1 : 0,
            'failed_count' => $processed ? 0 : 1,
        ]);
    }

    public function enqueueAll(array $jobs): void
    {
        $jobCount = count($jobs);
        $this->getLogger()->debug('Queue read started.', ['message_count' => $jobCount]);

        $successCount = 0;
        $failedCount = 0;

        foreach ($jobs as $job) {
            if ($this->process($job)) {
                $successCount++;
                continue;
            }

            $failedCount++;
        }

        $this->getLogger()->debug('Queue read finished.', [
            'message_count' => $jobCount,
            'processed_count' => $successCount + $failedCount,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    protected function process(JobInterface $job)
    {
        try {
            $acknowledged = (bool)$job->handle($this->serviceLocator);

            if (!$acknowledged) {
                $this->getLogger()->warning('Queue acknowledgement contract issue: job did not acknowledge processing.', [
                    'job_class' => get_class($job),
                ]);
            }

            return $acknowledged;
        } catch (\Exception $e) {
            $this->getLogger()->warning('Queue acknowledgement contract issue: job threw during acknowledgement.', [
                'job_class' => get_class($job),
            ]);
            $this->logException($e);
            return false;
        }
    }
}
