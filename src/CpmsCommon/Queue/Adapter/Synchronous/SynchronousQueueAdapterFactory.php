<?php

namespace CpmsCommon\Queue\Adapter\Synchronous;

use CpmsCommon\Service\LoggerAliasResolver;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SynchronousQueueAdapterFactory
 *
 * @package Queue\Adapter\Immediate
 */
class SynchronousQueueAdapterFactory
{
    /**
     * Create an object
     *
     * @param  ServiceLocatorInterface $container
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ServiceLocatorInterface $container)
    {
        $adapter = (new SynchronousQueueAdapter())->setServiceLocator($container);

        $loggerAlias = LoggerAliasResolver::resolve($container);
        if ($container->has($loggerAlias)) {
            $logger = $container->get($loggerAlias);

            if ($logger instanceof LoggerInterface) {
                $adapter->setLogger($logger);
            }
        }

        return $adapter;
    }
}
