<?php

declare(strict_types=1);

namespace PK\Config\DependencyInjection;

use Aws\Ssm\SsmClient;
use PK\Config\Environment\EntryConfiguration;
use PK\Config\Environment\Environment;
use PK\Config\Exception\LogicException;
use PK\Config\PKConfigBundle;
use PK\Config\StorageAdapter\NameResolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PKConfigExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));

        if ($this->isInstalledAsBundle($container)) {
            $loader->load('services_bundle.yaml');
        } else {
            $loader->load('services.yaml');
        }

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->doLoad($config, $container, $loader);
    }

    private function isInstalledAsBundle(ContainerBuilder $container): bool
    {
        return $container->hasParameter('kernel.bundles')
            && in_array(PKConfigBundle::class, $container->getParameter('kernel.bundles'));
    }

    /**
     * @param mixed[] $config
     */
    private function doLoad(array $config, ContainerBuilder $container, YamlFileLoader $loader): void
    {
        $adaptersMap = isset($config['adapters']) ?
            $this->processAdapters($config['adapters'], $container, $loader) :
            [];

        $environments = [];
        foreach ($config['envs'] as $name => $envConfig) {
            $environments[] = $this->processEnvConfig($name, $envConfig, $adaptersMap);
        }
        $container->getDefinition('pk.config')->setArgument('$environments', $environments);
    }

    /**
     * @param mixed[]  $config
     * @param mixed[]  $globalEntries
     * @param string[] $adaptersMap
     */
    private function processEnvConfig(
        string $env,
        array $config,
        array $adaptersMap
    ): Definition {
        $entries = $this->processEntries($config['entries']);
        $resolveFromMap = $this->processResolveFrom($config['entries']);

        $adapters = [];
        foreach ($config['adapters'] as $adapterId) {
            $adapters[] = isset($resolveFromMap[$adapterId]) ?
                new Definition(
                    NameResolver::class,
                    [
                        '$adapter' => $this->resolveAdapterReference($adaptersMap, $adapterId),
                        '$resolveFromMap' => $resolveFromMap[$adapterId],
                    ]
                ) :
                $this->resolveAdapterReference($adaptersMap, $adapterId);
        }

        return new Definition(
            Environment::class,
            [
                '$name' => $env,
                '$adapters' => $adapters,
                '$entriesConfiguration' => $entries,
            ]
        );
    }

    /**
     * @param mixed[] $config
     *
     * @return Definition[]
     */
    private function processEntries(array $config): array
    {
        $entries = [];
        foreach ($config as $name => $entryConfig) {
            $entries[] = new Definition(
                EntryConfiguration::class,
                [
                    '$name' => $name,
                    '$required' => $entryConfig['required'],
                    '$hasDefaultValue' => array_key_exists('default_value', $entryConfig),
                    '$defaultValue' => $entryConfig['default_value'] ?? null,
                ]
            );
        }

        return $entries;
    }

    /**
     * @param mixed[]  $entries
     * @param string[] $adapters
     *
     * @return string[][]
     */
    private function processResolveFrom(array $entries): array
    {
        $resolveFromMap = [];
        foreach ($entries as $name => $entry) {
            $resolveFrom = $entry['resolve_from'];
            if (empty($resolveFrom)) {
                continue;
            }
            foreach ($resolveFrom as $adapterId => $doResolveFrom) {
                $resolveFromMap[$adapterId][$doResolveFrom] = $name;
            }
        }

        return $resolveFromMap;
    }

    /**
     * @param string[] $adaptersMap
     */
    private function resolveAdapterReference(array $adaptersMap, string $adapterId): Reference
    {
        return new Reference($adaptersMap[$adapterId] ?? $adapterId);
    }

    /**
     * @param mixed[] $config
     *
     * @return string[]
     */
    private function processAdapters(array $config, ContainerBuilder $container, YamlFileLoader $loader): array
    {
        $adaptersMap = $this->processAwsSsm($config['aws_ssm'], $container, $loader);
        $adaptersMap = array_merge($adaptersMap, $this->processLocalEnv($config['local_env'], $container, $loader));

        return $adaptersMap;
    }

    /**
     * @param mixed[] $config
     *
     * @return string[]
     */
    private function processAwsSsm(array $config, ContainerBuilder $container, YamlFileLoader $loader): array
    {
        if (empty($config)) {
            return [];
        }
        if (!class_exists(SsmClient::class)) {
            throw new LogicException(
                'Cannot enable aws_ssm without AWS SDK. Try running "composer require aws/aws-sdk-php".'
            );
        }

        $adaptersMap = [];
        $loader->load('adapters/aws_ssm.yaml');

        foreach ($config as $name => $adapter) {
            $serviceId = "pk.config.adapter.ssm_client.$name";
            $container->setDefinition(
                $serviceId,
                new Definition(
                    $adapter['path'] ?
                        '%pk.config.adapter.ssm_by_path_client.class%' :
                        '%pk.config.adapter.ssm_client.class%',
                    array_filter([
                        '$ssmClient' => new Definition(
                            '%pk.config.aws.ssm_client.class%',
                            ['$args' => $adapter['client']]
                        ),
                        '$path' => $adapter['path'],
                    ])
                )
            );
            if ('default' === $name) {
                $adaptersMap['aws_ssm'] = $serviceId;
            }
            $adaptersMap["aws_ssm.$name"] = $serviceId;
        }

        return $adaptersMap;
    }

    /**
     * @param mixed[] $config
     *
     * @return string[]
     */
    private function processLocalEnv(array $config, ContainerBuilder $container, YamlFileLoader $loader): array
    {
        if (!$this->isConfigEnabled($container, $config)) {
            return [];
        }
        $loader->load('adapters/local_env.yaml');

        return [
            'local_env' => 'pk.config.adapter.local_env',
        ];
    }
}
