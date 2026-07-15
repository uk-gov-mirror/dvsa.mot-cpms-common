<?php

namespace CpmsCommon\Utility;

use Psr\Log\LoggerInterface;

interface LoggerAwareInterface
{
    public const EMERG = 0;
    public const ALERT = 1;
    public const CRIT = 2;
    public const ERR = 3;
    public const WARN = 4;
    public const NOTICE = 5;
    public const INFO = 6;
    public const DEBUG = 7;

    public function getLogger(): LoggerInterface;

    public function setLogger(LoggerInterface $logger): self;

    public function log(string $message, int $priority = self::INFO, array $extra = array()): void;

    public function logException(\Exception $exception): LoggerInterface;
}
