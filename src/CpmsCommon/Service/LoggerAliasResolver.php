<?php

namespace CpmsCommon\Service;

use Psr\Container\ContainerInterface;

class LoggerAliasResolver
{
    public const DEFAULT_LOGGER_ALIAS = 'cpms\\client\\logger';
    public const LEGACY_LOGGER_ALIAS = 'Logger';

    public static function resolve(ContainerInterface $container): string
    {
        $config = $container->has('config') ? (array)$container->get('config') : [];
        $configuredAlias = trim((string)($config['cpms_api']['logger_alias'] ?? ''));

        if ($configuredAlias !== '' && $container->has($configuredAlias)) {
            return $configuredAlias;
        }

        if ($container->has(self::DEFAULT_LOGGER_ALIAS)) {
            return self::DEFAULT_LOGGER_ALIAS;
        }

        return self::LEGACY_LOGGER_ALIAS;
    }
}

