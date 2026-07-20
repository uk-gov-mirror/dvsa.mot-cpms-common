<?php

namespace CpmsCommon\Service;

use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Formatter\PipeDelimitedFormatter;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

/**
 * Service factory for Common Logger
 *
 * @package       CpmsCommon
 * @subpackage    Service
 * @author        Pele Odiase <pele.odiase@valtech.co.uk>
 */
class LoggerServiceFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var array $serviceConfig */
        $serviceConfig = (array) $container->get('config');
        $loggerConfig = (array) ($serviceConfig['logger'] ?? []);

        $location = trim((string) ($loggerConfig['location'] ?? ''));
        if ($location === '') {
            $location = 'data/logs';
        }
        $location = rtrim($location, '/');

        $filename = trim((string) ($loggerConfig['filename'] ?? ''));
        if ($filename === '') {
            $filename = 'cpms-common.log';
        }

        $channel = (string) ($loggerConfig['channel'] ?? 'cpms-common');
        $path = $location . '/' . ltrim($filename, '/');

        if (!is_dir($location)) {
            mkdir($location, 0777, true);
        }

        if (!file_exists($path)) {
            touch($path);
        }

        $level = $this->resolveLevel($loggerConfig['priority'] ?? null);

        $handler = new StreamHandler($path, MonologLogger::toMonologLevel($level));
        $handler->setFormatter(new PipeDelimitedFormatter());

        $logger = new MonologLogger($channel);
        $logger->pushHandler($handler);

        $identityProvider = $container->has(IdentityProviderInterface::class)
            ? $container->get(IdentityProviderInterface::class)
            : null;

        $tokenService = $container->has(TokenServiceInterface::class)
            ? $container->get(TokenServiceInterface::class)
            : null;

        $requestUuid = isset($loggerConfig['request_uuid']) && is_string($loggerConfig['request_uuid'])
            ? $loggerConfig['request_uuid']
            : null;

        $includeToken = (bool) ($loggerConfig['include_token'] ?? false);

        return new LoggerService(
            $logger,
            $identityProvider,
            $tokenService,
            $requestUuid,
            $includeToken,
        );
    }

    private function resolveLevel($priority): string
    {
        if (is_string($priority) && $priority !== '') {
            return strtolower($priority);
        }

        if (!is_int($priority)) {
            return 'debug';
        }

        return match ($priority) {
            0 => 'emergency',
            1 => 'alert',
            2 => 'critical',
            3 => 'error',
            4 => 'warning',
            5 => 'notice',
            6 => 'info',
            default => 'debug',
        };
    }
}
