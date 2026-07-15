<?php

namespace CpmsCommon\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ClientLoggerFactory implements FactoryInterface
{
    public const DEFAULT_LOCATION = 'data/logs';
    public const DEFAULT_FILENAME = 'cpms-client.log';
    public const DEFAULT_CHANNEL = 'cpms-client';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LoggerInterface
    {
        $config = $container->has('config') ? (array)$container->get('config') : [];
        $loggerConfig = (array)($config['cpms_client']['logger'] ?? []);

        $location = (string)($loggerConfig['location'] ?? self::DEFAULT_LOCATION);
        $filename = (string)($loggerConfig['filename'] ?? self::DEFAULT_FILENAME);
        $channel = (string)($loggerConfig['channel'] ?? self::DEFAULT_CHANNEL);

        if ($location === '') {
            $location = self::DEFAULT_LOCATION;
        }

        if ($filename === '') {
            $filename = self::DEFAULT_FILENAME;
        }

        if ($channel === '') {
            $channel = self::DEFAULT_CHANNEL;
        }

        $filePath = rtrim($location, '/') . '/' . trim($filename, '/');
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $logger = new Logger($channel);
        $logger->pushHandler(new StreamHandler($filePath, Level::Debug));

        return $logger;
    }
}

