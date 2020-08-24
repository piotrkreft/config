<?php

declare(strict_types=1);

namespace PK\Config\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerFactory implements ContainerFactoryInterface
{
    public function create(?string $configurationFile = null): ContainerInterface
    {
        $containerBuilder = $this->getContainerBuilder();
        if ($configurationFile) {
            $containerBuilder->registerExtension(new PKConfigExtension());
        }
        $containerBuilder->addCompilerPass(new AddConsoleCommandPass());

        $this->loadConfiguration($containerBuilder, $configurationFile);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    protected function getContainerBuilder(): ContainerBuilder
    {
        return new ContainerBuilder();
    }

    protected function loadConfiguration(ContainerBuilder $containerBuilder, ?string $configurationFile): void
    {
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__ . '/../..'));
        $loader->load($configurationFile ?? 'src/Resources/config/services.yaml');
    }
}
