<?php

namespace CpmsCommon\Service;

use DvsaLogger\Factory\MotLoggerFactory;
use Laminas\ServiceManager\Factory\FactoryInterface;
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
        $motLogger = (new MotLoggerFactory())
            ->create($this->buildMotLoggerConfig($container));

        return $motLogger->getLogger();
    }

    private function buildMotLoggerConfig(ContainerInterface $container): array
    {
        $config = $container->has('config') ? (array) $container->get('config') : [];
        $loggerConfig = (array) ($config['cpms_client']['logger'] ?? []);

        $location = $this->resolveConfigValue($loggerConfig, 'location', self::DEFAULT_LOCATION);
        $filename = $this->resolveConfigValue($loggerConfig, 'filename', self::DEFAULT_FILENAME);
        $channel = $this->resolveConfigValue($loggerConfig, 'channel', self::DEFAULT_CHANNEL);

        $filePath = rtrim($location, '/') . '/' . trim($filename, '/');
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return [
            'channel' => $channel,
            'register_error_handler' => false,
            'writers' => [
                [
                    'type' => 'stream',
                    'path' => $filePath,
                    'formatter' => 'pipe',
                    'level' => 'debug',
                    'enabled' => true,
                ],
            ],
        ];
    }

    private function resolveConfigValue(array $loggerConfig, string $key, string $default): string
    {
        $value = trim((string) ($loggerConfig[$key] ?? ''));

        return $value === '' ? $default : $value;
    }
}

