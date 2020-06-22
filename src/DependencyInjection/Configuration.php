<?php

declare(strict_types=1);

namespace PK\Config\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    private const ALL_MARKER = '__ALL__';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pk_config');

        $treeBuilder
            ->getRootNode()
                ->validate()
                    ->always(function (array $v): array {
                        return $this->mergeEntries($v);
                    })
                ->end()
                ->children()
                    ->arrayNode('envs')
                        ->isRequired()
                        ->requiresAtLeastOneElement()
                        ->useAttributeAsKey('env')
                        ->arrayPrototype()
                            ->children()
                                ->arrayNode('adapters')
                                    ->info(
                                        'Adapters order is also a priority. Values will be resolved from highest placed'
                                    )
                                    ->isRequired()
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function (string $v): array {
                                            return [$v];
                                        })
                                    ->end()
                                    ->requiresAtLeastOneElement()
                                    ->scalarPrototype()->end()
                                ->end()
                                ->append($this->entries(true))
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->entries())
                    ->arrayNode('adapters')
                        ->children()
                            ->arrayNode('aws_ssm')
                                ->info('Integrates AWS Simple Systems Manager.')
                                ->canBeEnabled()
                                ->children()
                                    ->arrayNode('client')
                                        ->isRequired()
                                        ->children()
                                            ->arrayNode('credentials')
                                                ->isRequired()
                                                ->children()
                                                    ->scalarNode('key')->isRequired()->end()
                                                    ->scalarNode('secret')->isRequired()->end()
                                                ->end()
                                            ->end()
                                            ->scalarNode('version')->defaultValue('latest')->end()
                                            ->scalarNode('region')->isRequired()->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('path')
                                        ->info(
                                            'If provided parameters will be fetched by path.
                                            `{env}` substring will be replaced on fetching with given environment.'
                                        )
                                        ->defaultValue(null)
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('local_env')
                                ->info('Integrates local environment variables.')
                                ->canBeEnabled()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function entries(bool $inEnvironment = false): ArrayNodeDefinition
    {
        $definition = new ArrayNodeDefinition('entries');

        $entriesConfig = $definition
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->validate()
                    ->ifTrue(function (array $v): bool {
                        return !$v['required'] && array_key_exists('default_value', $v);
                    })
                    ->thenInvalid('Cannot set `required` as false and `default_value`.')
                ->end()
                ->children()
                    ->booleanNode('required')
                        ->defaultTrue()
                    ->end()
                    ->variableNode('default_value')->end()
                    ->scalarNode('description')->end();

        if ($inEnvironment) {
            $entriesConfig
                ->booleanNode('disabled')->defaultFalse()->end()
                ->arrayNode('resolve_from')
                    ->info('The name from which adapter value will be resolved. Can be per adapter or for all in env.')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function (string $v): array {
                            return [self::ALL_MARKER => $v];
                        })
                    ->end()
                    ->useAttributeAsKey('adapter')
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()->end()
                ->end()
            ;
        } else {
            $entriesConfig
                ->scalarNode('resolve_from')
                    ->info('The name from which adapter value will be resolved. Resolved for all adapters in env.')
                ->end()
            ;
        }

        return $definition;
    }

    /**
     * @param mixed[] $configuration
     *
     * @return mixed[]
     */
    private function mergeEntries(array $configuration): array
    {
        foreach ($configuration['envs'] as $name => $env) {
            $merged = array_merge($configuration['entries'], $env['entries']);
            foreach ($merged as $var => $entry) {
                if ($entry['disabled'] ?? false) {
                    unset($merged[$var]);
                    continue;
                }
                $merged[$var]['resolve_from'] = $this->normalizeResolveFrom(
                    $env['adapters'],
                    $entry['resolve_from'] ?? null,
                    "pk_config.envs.$name.entries.$var.resolve_from"
                );
            }

            $configuration['envs'][$name]['entries'] = $merged;
        }
        unset($configuration['entries']);

        return $configuration;
    }

    /**
     * @param string[]             $adapters
     * @param string|string[]|null $resolveFrom
     *
     * @return string[]
     */
    private function normalizeResolveFrom(array $adapters, $resolveFrom, string $path): array
    {
        if (null === $resolveFrom) {
            return [];
        }
        if (!$doResolveFrom = $this->resolveFromAllName($resolveFrom)) {
            foreach (array_keys($resolveFrom) as $adapterId) {
                if (!in_array($adapterId, $adapters, true)) {
                    throw new InvalidConfigurationException(
                        "Invalid configuration for path '$path': '$adapterId' adapter not configured for environment."
                    );
                }
            }

            return $resolveFrom;
        }

        return array_fill_keys($adapters, $doResolveFrom);
    }

    /**
     * @param string|string[] $resolveFrom
     */
    private function resolveFromAllName($resolveFrom): ?string
    {
        if (is_string($resolveFrom)) {
            return $resolveFrom;
        }

        return $resolveFrom[self::ALL_MARKER] ?? null;
    }
}
