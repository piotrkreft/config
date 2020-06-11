<?php

declare(strict_types=1);

namespace PK\Config;

use PK\Config\DependencyInjection\ContainerFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigFactory
{
    public static function create(string $configurationFile): ConfigInterface
    {
        $factory = new ContainerFactory();
        $container = $factory->create($configurationFile);
        $config = $container->get('pk.config');

        if (!$config instanceof ConfigInterface) {
            throw new InvalidConfigurationException(sprintf(
                'Config should be instance of %s. %s given.',
                ConfigInterface::class,
                get_debug_type($config)
            ));
        }

        return $config;
    }
}
