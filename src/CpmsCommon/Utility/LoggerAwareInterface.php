<?php

namespace CpmsCommon\Utility;

use CpmsCommon\Service\LoggerService;

interface LoggerAwareInterface
{
    public function getLogger(): LoggerService;

    public function setLogger(LoggerService $logger): self;

    public function log(string $message, int $priority = LoggerService::INFO, array $extra = array()): void;

    public function logException(\Exception $exception): LoggerService;
}
