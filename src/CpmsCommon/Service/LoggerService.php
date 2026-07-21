<?php

namespace CpmsCommon\Service;

use DvsaLogger\Logger\MotLogger;
use Monolog\Level;

class LoggerService extends MotLogger
{
    public function log(Level|int $level, string $message, array $context = []): self
    {
        parent::log($level, $message, $context);

        return $this;
    }

    public function debug(string $message, array $context = []): self
    {
        parent::debug($message, $context);

        return $this;
    }

    public function error(string $message, array $context = []): self
    {
        parent::error($message, $context);

        return $this;
    }

    /**
     * Backward-compatible helper while call sites move to native mot-logger APIs.
     */
    public function logException(\Exception $exception): self
    {
        $this->error($exception->getMessage(), ['ex' => $exception]);

        return $this;
    }
}
