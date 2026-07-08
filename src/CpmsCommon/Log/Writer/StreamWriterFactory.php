<?php

namespace CpmsCommon\Log\Writer;

use CpmsCommon\Log\LogDataAwareInterface;
use CpmsCommon\Log\LogData;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory for Stream Log Handler (Monolog StreamHandler)
 */
class StreamWriterFactory implements FactoryInterface
{
    private array $logConfig = [];

    /**
     * @param  ContainerInterface $container
     * @param  null|string $requestedName
     * @param  null|array $options
     * @return StreamHandler
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var array $serviceConfig */
        $serviceConfig = $container->get('config');
        $this->logConfig = $serviceConfig['logger'];
        $level = $this->getMonologLevel();
        $filePath = $this->getFilePath();
        $logData = null;

        $handler = new StreamHandler($filePath, $level);

        if (!empty($serviceConfig['logger']['replacement'])) {
            /** @var LogData $logData */
            $logData = $container->get($serviceConfig['logger']['replacement']);
        }

        if (!empty($this->logConfig['formatter'])) {
            /** @var FormatterInterface&LogDataAwareInterface $formatter */
            $formatter = $container->get($this->logConfig['formatter']);
            $handler->setFormatter($formatter);

            if ($logData instanceof LogDataAwareInterface) {
                $formatter->setLogData($logData);
            }
        }

        return $handler;
    }

    /**
     * Check log location and create directory if needed
     */
    private function checkLogDirectory(): string
    {
        if (isset($this->logConfig['location']) && !\file_exists($this->logConfig['location'])) {
            \mkdir($this->logConfig['location'], $this->logConfig['mode'], true);
        }

        if (empty($this->logConfig['location'])) {
            $location = sys_get_temp_dir();
        } else {
            $location = $this->logConfig['location'];
        }

        return $location;
    }

    /**
     * Get log filename
     */
    private function getLogFilename(): string
    {
        if (empty($this->logConfig['filename'])) {
            $filename = \date('Y-m-d') . '-app.log';
        } else {
            $filename = $this->logConfig['filename'];
        }

        return $filename;
    }

    /**
     * Map syslog/Laminas integer priority to Monolog Level enum
     */
    private function getMonologLevel(): Level
    {
        $priority = $this->logConfig['priority'] ?? \LOG_DEBUG;

        return match ((int) $priority) {
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

    /**
     * Get full file path, creating the file if it does not exist
     */
    private function getFilePath(): string
    {
        $filename = $this->getLogFilename();
        $location = $this->checkLogDirectory();
        $filePath = $location . \DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath)) {
            touch($filePath);
            chmod($filePath, $this->logConfig['mode']);
        }

        return $filePath;
    }
}
