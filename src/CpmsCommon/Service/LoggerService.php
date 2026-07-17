<?php

namespace CpmsCommon\Service;

use DvsaLogger\Logger\MotLogger;
use Monolog\Level;

class LoggerService extends MotLogger
{
    public function log(Level|int $level, string $message, array $context = []): self
    {
        return parent::log($level, $message, $context);
    }

    public function debug(string $message, array $context = []): self
    {
        return parent::debug($message, $context);
    }

    public function error(string $message, array $context = []): self
    {
        return parent::error($message, $context);
    }

    /**
     * Backward-compatible helper while call sites move to native mot-logger APIs.
     */
    public function logException(\Exception $exception)
    {
        $this->error($exception->getMessage(), ['ex' => $exception]);

        return $this;
    }
}
