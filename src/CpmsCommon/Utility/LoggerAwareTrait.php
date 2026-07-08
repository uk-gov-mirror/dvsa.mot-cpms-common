<?php

/**
 * A trait so that controllers can easily integrate logging.
 */

namespace CpmsCommon\Utility;

use CpmsCommon\Service\LoggerService;
use Laminas\ServiceManager\ServiceManager;

/**
 * Class LoggerAwareTrait
 * The class using this trait must implement the ServiceLocatorAwareInterface
 * @method ServiceManager getServiceLocator()
 *
 * @package CpmsCommon\Utility
 */
trait LoggerAwareTrait
{
    protected ?LoggerService $logger = null;

    /**
     * Returns an instantiated instance of Zend Log.
     *
     * @throws \InvalidArgumentException
     */
    public function getLogger(): LoggerService
    {
        if (null === $this->logger) {
            /** @var LoggerService $logger */
            $logger = $this->getServiceLocator()->get('Logger');
            $this->setLogger($logger);
        }

        /** @var LoggerService */
        return $this->logger;
    }

    /**
     * Set logger object
     */
    public function setLogger(LoggerService $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Logs a message to the defined logger.
     */
    public function log(string $message, int $priority = LoggerService::INFO, array $extra = array()): void
    {
        $this->getLogger()->log($priority, $message, $extra);
    }

    /**
     * Logs an exception
     *
     * @param \Exception $exception
     *
     * @return LoggerService
     */
    public function logException(\Exception $exception): LoggerService
    {
        return $this->getLogger()->logException($exception);
    }
}
