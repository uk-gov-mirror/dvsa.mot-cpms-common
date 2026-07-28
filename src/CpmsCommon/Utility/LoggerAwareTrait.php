<?php

/**
 * A trait so that controllers can easily integrate logging.
 */

namespace CpmsCommon\Utility;

use DvsaLogger\Logger\MotLogger;
use Laminas\ServiceManager\ServiceManager;
use Monolog\Level;

/**
 * Class LoggerAwareTrait
 * The class using this trait must implement the ServiceLocatorAwareInterface
 * @method ServiceManager getServiceLocator()
 *
 * @package CpmsCommon\Utility
 */
trait LoggerAwareTrait
{
    protected ?MotLogger $logger = null;

    /**
     * Returns an instantiated logger service.
     *
     * @throws \InvalidArgumentException
     */
    public function getLogger(): MotLogger
    {
        if (null === $this->logger) {
            /** @var MotLogger $logger */
            $logger = $this->getServiceLocator()->get('Logger');
            $this->setLogger($logger);
        }

        /** @var MotLogger */
        return $this->logger;
    }

    /**
     * Set logger object
     */
    public function setLogger(MotLogger $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Logs a message to the defined logger.
     */
    public function log(string $message, Level|int $priority = Level::Info, array $extra = array()): void
    {
        $this->getLogger()->log($priority, $message, $extra);
    }

    /**
     * Logs an exception
     *
     * @param \Exception $exception
     *
     * @return MotLogger
     */
    public function logException(\Exception $exception): MotLogger
    {
        return $this->getLogger()->error($exception->getMessage(), ['ex' => $exception]);
    }
}
