<?php

declare(strict_types=1);

namespace PK\Config;

use PK\Config\DependencyInjection\ContainerFactory;
use PK\Config\DependencyInjection\ContainerFactoryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigFactory
{
    public static function create(
        string $configurationFile,
        ?ContainerFactoryInterface $containerFactory = null
    ): ConfigInterface {
        if (!$containerFactory) {
            $containerFactory = new ContainerFactory();
        }
        $container = $containerFactory->create($configurationFile);
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
