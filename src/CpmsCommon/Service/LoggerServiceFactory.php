<?php

namespace CpmsCommon\Service;

use CpmsCommon\Log\LogData;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as MonologLogger;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory for Common Logger
 */
class LoggerServiceFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $monoLogger = new MonologLogger('cpms-common');
        $log = new LoggerService($monoLogger);

        /** @var array $serviceConfig */
        $serviceConfig = $container->get('config');
        $logData = null;
        $writers = (array)$serviceConfig['logger']['writers'];
        $writers = array_unique($writers);

        if (!empty($serviceConfig['logger']['replacement'])) {
            $logData = $container->get($serviceConfig['logger']['replacement']);
        }

        if ($logData instanceof LogData) {
            $logData->setStrictMode(false);
            $log->setLogData($logData);
        }

        foreach ($writers as $logWriter) {
            /** @var HandlerInterface $handler */
            $handler = $container->get($logWriter);
            $log->addHandler($handler);
        }

        return $log;
    }
}
