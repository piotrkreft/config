<?php

declare(strict_types=1);

namespace PK\Tests\Config\Fixtures;

use PK\Config\PKConfigBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new PKConfigBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
    }

    public function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/Resources/config/config_bundle.yaml');
        $loader->load(__DIR__ . '/Resources/config/config.yaml');
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/../../var/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../../var/logs';
    }
}
