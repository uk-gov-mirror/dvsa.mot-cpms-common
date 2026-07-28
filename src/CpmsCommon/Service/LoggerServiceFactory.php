<?php

namespace CpmsCommon\Service;

use DvsaLogger\Contract\IdentityProviderInterface;
use DvsaLogger\Contract\TokenServiceInterface;
use DvsaLogger\Formatter\PipeDelimitedFormatter;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
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

        $handler = new StreamHandler($path, $level);
        $handler->setFormatter(new PipeDelimitedFormatter());

        $logger = new MonologLogger($channel);
        $logger->pushHandler($handler);

        $identityProvider = null;
        if ($container->has(IdentityProviderInterface::class)) {
            $candidate = $container->get(IdentityProviderInterface::class);
            if ($candidate instanceof IdentityProviderInterface) {
                $identityProvider = $candidate;
            }
        }

        $tokenService = null;
        if ($container->has(TokenServiceInterface::class)) {
            $candidate = $container->get(TokenServiceInterface::class);
            if ($candidate instanceof TokenServiceInterface) {
                $tokenService = $candidate;
            }
        }

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

    private function resolveLevel(null|int|string|Level $priority): Level
    {
        if ($priority instanceof Level) {
            return $priority;
        }

        if (is_string($priority) && $priority !== '') {
            return match (strtolower($priority)) {
                'emergency' => Level::Emergency,
                'alert' => Level::Alert,
                'critical' => Level::Critical,
                'error' => Level::Error,
                'warning' => Level::Warning,
                'notice' => Level::Notice,
                'info' => Level::Info,
                default => Level::Debug,
            };
        }

        if (!is_int($priority)) {
            return Level::Debug;
        }

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
