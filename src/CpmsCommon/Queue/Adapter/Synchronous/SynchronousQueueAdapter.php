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
        $this->process($job);
    }

    public function enqueueAll(array $jobs): void
    {
        array_map([$this, 'enqueue'], $jobs);
    }

    /**
     * @param JobInterface $job
     *
     * @return bool
     */
    protected function process(JobInterface $job)
    {
        try {
            return $job->handle($this->serviceLocator);
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage(), ['ex' => $e]);
            return false;
        }
    }
}
