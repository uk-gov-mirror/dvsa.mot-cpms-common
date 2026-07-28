<?php

namespace CpmsCommon\Utility;

use DvsaLogger\Logger\MotLogger;
use Monolog\Level;

interface LoggerAwareInterface
{
    public function getLogger(): MotLogger;

    public function setLogger(MotLogger $logger): self;

    public function log(string $message, Level|int $priority = Level::Info, array $extra = array()): void;

    public function logException(\Exception $exception): MotLogger;
}
