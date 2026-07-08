<?php

namespace CpmsCommon\Service;

use CpmsCommon\Log\LogDataAwareInterface;
use CpmsCommon\Log\LogDataAwareTrait;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class LoggerService implements LogDataAwareInterface
{
    use LogDataAwareTrait;

    // Backward-compatible Laminas/syslog-style integer log level constants
    const EMERG  = 0;
    const ALERT  = 1;
    const CRIT   = 2;
    const ERR    = 3;
    const WARN   = 4;
    const NOTICE = 5;
    const INFO   = 6;
    const DEBUG  = 7;

    private readonly MonologLogger $logger;

    public function __construct(?MonologLogger $logger = null)
    {
        $this->logger = $logger ?? new MonologLogger('cpms-common');
    }

    public function log(int $priority, string $message, array $extra = []): self
    {
        $this->logger->log($this->mapPriorityToLevel($priority), $message, $extra);
        return $this;
    }

    public function err(string $message, array $context = []): self
    {
        $this->logger->error($message, $context);
        return $this;
    }

    public function info(string $message, array $context = []): self
    {
        $this->logger->info($message, $context);
        return $this;
    }

    public function debug(string $message, array $context = []): self
    {
        $this->logger->debug($message, $context);
        return $this;
    }

    public function warn(string $message, array $context = []): self
    {
        $this->logger->warning($message, $context);
        return $this;
    }

    public function notice(string $message, array $context = []): self
    {
        $this->logger->notice($message, $context);
        return $this;
    }

    public function crit(string $message, array $context = []): self
    {
        $this->logger->critical($message, $context);
        return $this;
    }

    public function alert(string $message, array $context = []): self
    {
        $this->logger->alert($message, $context);
        return $this;
    }

    public function emerg(string $message, array $context = []): self
    {
        $this->logger->emergency($message, $context);
        return $this;
    }

    public function addHandler(HandlerInterface $handler): void
    {
        $this->logger->pushHandler($handler);
    }

    /**
     * @param \Exception $exception
     * @return $this
     */
    public function logException(\Exception $exception): self
    {
        $this->getLogData()->setEntryType('exception');
        $this->err($this->processException($exception, false));

        return $this;
    }

    /**
     * Format exception
     *
     * @param \Exception $exception
     * @param bool       $returnLog
     * @return string
     */
    public function processException(\Exception $exception, bool $returnLog = true): string
    {
        $log   = '';
        $index = 1;
        $trace = $exception->getTraceAsString();

        $this->getLogData()->setExceptionCode($exception->getCode());
        $this->getLogData()->setExceptionMessage($exception->getMessage());
        $this->getLogData()->setStackTrace($trace);
        $this->getLogData()->setExceptionType(get_class($exception));

        if ($returnLog) {
            do {
                $messages[] = $index++ . ": " . $exception->getMessage();
            } while ($exception = $exception->getPrevious());

            $log .= "Exception:\n" . implode("\n", $messages) . "\nTrace:\n" . $trace . "\n\n";
        }

        return $log;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function addReplacement(string $key, mixed $value): self
    {
        $this->getLogData()->{$key} = $value;

        return $this;
    }

    private function mapPriorityToLevel(int $priority): Level
    {
        return match ($priority) {
            0 => Level::Emergency,
            1 => Level::Alert,
            2 => Level::Critical,
            3 => Level::Error,
            4 => Level::Warning,
            5 => Level::Notice,
            6 => Level::Info,
            default => Level::Debug,
        };
    }
}
