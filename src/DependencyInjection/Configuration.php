<?php

declare(strict_types=1);

namespace PK\Config\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pk_config');

        $treeBuilder
            ->getRootNode()
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
                                        ->then(function ($v): array {
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

    private function entries(bool $canBeDisabled = false): ArrayNodeDefinition
    {
        $definition = new ArrayNodeDefinition('entries');

        $entriesConfig = $definition
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->validate()
                    ->ifTrue(function (array $v) {
                        return !$v['required'] && isset($v['default_value']);
                    })
                    ->thenInvalid('Cannot set `required` as false and `default_value`.')
                ->end()
                ->children()
                    ->booleanNode('required')
                        ->defaultTrue()
                    ->end()
                    ->variableNode('default_value')->end()
                    ->scalarNode('resolve_from')
                        ->info('The name from which adapter value will be resolved.')
                    ->end()
                    ->scalarNode('description')->end();

        if ($canBeDisabled) {
            $entriesConfig->booleanNode('disabled')->defaultFalse()->end();
        }

        return $definition;
    }
}
