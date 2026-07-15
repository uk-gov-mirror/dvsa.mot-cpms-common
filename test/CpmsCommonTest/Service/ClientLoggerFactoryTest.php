<?php

namespace CpmsCommonTest\Service;

use CpmsCommon\Service\ClientLoggerFactory;
use Laminas\ServiceManager\ServiceManager;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ClientLoggerFactoryTest extends TestCase
{
    private array $cleanupPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupPaths as $filePath) {
            if (is_file($filePath)) {
                unlink($filePath);
            }

            $directory = dirname($filePath);
            if (is_dir($directory) && count(scandir($directory) ?: []) === 2) {
                rmdir($directory);
            }
        }
    }

    public function testUsesDefaultPathAndChannelWhenConfigMissing(): void
    {
        $serviceManager = new ServiceManager([
            'services' => [
                'config' => [],
            ],
        ]);

        $factory = new ClientLoggerFactory();
        $logger = $factory($serviceManager, 'cpms\\client\\logger');
        $logger->info('default logger test');

        $this->cleanupPaths[] = 'data/logs/cpms-client.log';

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame('cpms-client', $logger->getName());
        $this->assertFileExists('data/logs/cpms-client.log');
    }

    public function testUsesConfiguredChannelAndPath(): void
    {
        $location = 'data/logs/client-logger-test';
        $filename = 'custom.log';

        $serviceManager = new ServiceManager([
            'services' => [
                'config' => [
                    'cpms_client' => [
                        'logger' => [
                            'location' => $location,
                            'filename' => $filename,
                            'channel' => 'custom-channel',
                        ],
                    ],
                ],
            ],
        ]);

        $factory = new ClientLoggerFactory();
        $logger = $factory($serviceManager, 'cpms\\client\\logger');
        $logger->info('configured logger test');

        $expectedPath = $location . '/' . $filename;
        $this->cleanupPaths[] = $expectedPath;

        $this->assertSame('custom-channel', $logger->getName());
        $this->assertFileExists($expectedPath);
    }
}


