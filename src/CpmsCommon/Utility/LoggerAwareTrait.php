<?php

/**
 * A trait so that controllers can easily integrate logging.
 */

namespace CpmsCommon\Utility;

use CpmsCommon\Service\LoggerAliasResolver;
use Laminas\ServiceManager\ServiceManager;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * Class LoggerAwareTrait
 * The class using this trait must implement the ServiceLocatorAwareInterface
 * @method ServiceManager getServiceLocator()
 *
 * @package CpmsCommon\Utility
 */
trait LoggerAwareTrait
{
    protected ?LoggerInterface $logger = null;

    /**
     * Returns an instantiated instance of Zend Log.
     *
     * @throws \InvalidArgumentException
     */
    public function getLogger(): LoggerInterface
    {
        if (null === $this->logger) {
            $loggerAlias = LoggerAliasResolver::resolve($this->getServiceLocator());
            $logger = $this->getServiceLocator()->get($loggerAlias);

            if (!$logger instanceof LoggerInterface) {
                throw new \InvalidArgumentException(sprintf('Logger service "%s" must implement %s', $loggerAlias, LoggerInterface::class));
            }

            $this->setLogger($logger);
        }

        return $this->logger;
    }

    /**
     * Set logger object
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Logs a message to the defined logger.
     */
    public function log(string $message, int $priority = LoggerAwareInterface::INFO, array $extra = array()): void
    {
        $this->getLogger()->log($this->mapPriorityToLevel($priority), $message, $extra);
    }

    /**
     * Logs an exception
     *
     * @param \Exception $exception
     *
     * @return LoggerInterface
     */
    public function logException(\Exception $exception): LoggerInterface
    {
        $this->getLogger()->error($exception->getMessage(), ['exception' => $exception]);

        return $this->getLogger();
    }

    private function mapPriorityToLevel(int $priority): string
    {
        return match ($priority) {
            LoggerAwareInterface::EMERG => LogLevel::EMERGENCY,
            LoggerAwareInterface::ALERT => LogLevel::ALERT,
            LoggerAwareInterface::CRIT => LogLevel::CRITICAL,
            LoggerAwareInterface::ERR => LogLevel::ERROR,
            LoggerAwareInterface::WARN => LogLevel::WARNING,
            LoggerAwareInterface::NOTICE => LogLevel::NOTICE,
            LoggerAwareInterface::INFO => LogLevel::INFO,
            default => LogLevel::DEBUG,
        };
    }
}
