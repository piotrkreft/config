<?php

declare(strict_types=1);

namespace PK\Config\DependencyInjection
{
    use Aws\Ssm\SsmClient;
    use PK\Tests\Config\DependencyInjection\PKConfigExtensionTest;

    function class_exists(string $className): bool
    {
        if (PKConfigExtensionTest::$mockClassExists && SsmClient::class === $className) {
            return false;
        }

        return \class_exists($className);
    }
}
namespace PK\Tests\Config\DependencyInjection
{
    use PHPUnit\Framework\TestCase;
    use PK\Config\DependencyInjection\PKConfigExtension;
    use PK\Config\Environment\EntryConfiguration;
    use PK\Config\Exception\LogicException;
    use PK\Config\PKConfigBundle;
    use PK\Config\StorageAdapter\LocalEnv;
    use PK\Config\StorageAdapter\NameResolver;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Definition;
    use Symfony\Component\DependencyInjection\Reference;

    class PKConfigExtensionTest extends TestCase
    {
        /**
         * @var bool
         */
        public static $mockClassExists = false;

        /**
         * @var PKConfigExtension
         */
        private $extension;

        protected function setUp(): void
        {
            $this->extension = new PKConfigExtension();
        }

        protected function tearDown(): void
        {
            self::$mockClassExists = false;
        }

        /**
         * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
         */
        public function testShouldLoadConfiguration(): void
        {
            // given
            $configuration = [
                'envs' => [
                    'dev' => [
                        'adapters' => [
                            'aws_ssm',
                            'local_env',
                            'custom_service_id',
                        ],
                        'entries' => [
                            'ENV_VAR_3' => [
                                'required' => true,
                                'resolve_from' => [
                                    'aws_ssm' => 'RESOLVED_ENV_VAR_3',
                                ],
                            ],
                            'ENV_VAR_4' => [
                                'disabled' => true,
                                'description' => 'Might be useful',
                            ],
                            'ENV_VAR_5' => [
                                'resolve_from' => [
                                    'custom_service_id' => 'some_var',
                                ],
                            ],
                        ],
                    ],
                    'stage' => [
                        'adapters' => 'aws_ssm.specific',
                        'entries' => [
                            'ENV_VAR_4' => [
                                'disabled' => true,
                                'resolve_from' => 'makes_no_sense',
                            ],
                            'ENV_VAR_2' => [
                                'disabled' => true,
                            ],
                        ],
                    ],
                ],
                'entries' => [
                    'ENV_VAR_1' => [],
                    'ENV_VAR_2' => [
                        'default_value' => 'var_2_default',
                    ],
                    'ENV_VAR_3' => [
                        'required' => false,
                    ],
                    'ENV_VAR_4' => [
                        'resolve_from' => 'RESOLVED_ENV_VAR_4',
                    ],
                    'ENV_VAR_5' => [
                        'resolve_from' => 'RESOLVED_VAR_5',
                        'default_value' => null,
                    ],
                ],
                'adapters' => [
                    'local_env' => true,
                    'aws_ssm' => [
                        'default' => [
                            'client' => [
                                'credentials' => [
                                    'key' => 'key',
                                    'secret' => 'secret',
                                ],
                                'version' => 'latest',
                                'region' => 'EU',
                            ],
                            'path' => '/path',
                        ],
                        'specific' => [
                            'client' => [
                                'credentials' => [
                                    'key' => 'key_specific',
                                    'secret' => 'secret',
                                ],
                                'version' => 'version',
                                'region' => 'EU',
                            ],
                        ],
                    ],
                ],
            ];
            $container = new ContainerBuilder();

            // when
            $this->extension->load([$configuration], $container);

            // then
            $this->assertTrue($container->hasDefinition('pk.config.adapter.ssm_client.default'));
            $this->assertEquals(
                new Definition(
                    '%pk.config.adapter.ssm_by_path_client.class%',
                    [
                        '$ssmClient' => new Definition(
                            '%pk.config.aws.ssm_client.class%',
                            [
                                '$args' => [
                                    'credentials' => [
                                        'key' => 'key',
                                        'secret' => 'secret',
                                    ],
                                    'version' => 'latest',
                                    'region' => 'EU',
                                ],
                            ]
                        ),
                        '$path' => '/path',
                    ]
                ),
                $container->getDefinition('pk.config.adapter.ssm_client.default')
            );
            $this->assertTrue($container->hasDefinition('pk.config.adapter.ssm_client.specific'));
            $this->assertEquals(
                new Definition(
                    '%pk.config.adapter.ssm_client.class%',
                    [
                        '$ssmClient' => new Definition(
                            '%pk.config.aws.ssm_client.class%',
                            [
                                '$args' => [
                                    'credentials' => [
                                        'key' => 'key_specific',
                                        'secret' => 'secret',
                                    ],
                                    'version' => 'version',
                                    'region' => 'EU',
                                ],
                            ]
                        ),
                    ]
                ),
                $container->getDefinition('pk.config.adapter.ssm_client.specific')
            );
            $this->assertTrue($container->hasDefinition('pk.config.adapter.local_env'));
            $environmentsDefinitions = $container->getDefinition('pk.config')->getArgument('$environments');
            $this->assertCount(2, $environmentsDefinitions);
            $this->assertEquals('dev', $environmentsDefinitions[0]->getArgument('$name'));
            $this->assertEquals('stage', $environmentsDefinitions[1]->getArgument('$name'));
            $this->assertEquals(
                [
                    $this->configurationDefinition('ENV_VAR_1', true, false, null),
                    $this->configurationDefinition('ENV_VAR_2', true, true, 'var_2_default'),
                    $this->configurationDefinition('ENV_VAR_3', true, false, null),
                    $this->configurationDefinition('ENV_VAR_5', true, false, null),
                ],
                $environmentsDefinitions[0]->getArgument('$entriesConfiguration')
            );
            $this->assertEquals(
                [
                    $this->configurationDefinition('ENV_VAR_1', true, false, null),
                    $this->configurationDefinition('ENV_VAR_3', false, false, null),
                    $this->configurationDefinition('ENV_VAR_5', true, true, null),
                ],
                $environmentsDefinitions[1]->getArgument('$entriesConfiguration')
            );
            $this->assertEquals(
                [
                    new Definition(
                        NameResolver::class,
                        [
                            '$adapter' => new Reference('pk.config.adapter.ssm_client.default'),
                            '$resolveFromMap' => ['RESOLVED_ENV_VAR_3' => 'ENV_VAR_3'],
                        ]
                    ),
                    new Reference('pk.config.adapter.local_env'),
                    new Definition(
                        NameResolver::class,
                        [
                            '$adapter' => new Reference('custom_service_id'),
                            '$resolveFromMap' => ['some_var' => 'ENV_VAR_5'],
                        ]
                    ),
                ],
                $environmentsDefinitions[0]->getArgument('$adapters')
            );
            $this->assertEquals(
                [
                    new Definition(
                        NameResolver::class,
                        [
                            '$adapter' => new Reference('pk.config.adapter.ssm_client.specific'),
                            '$resolveFromMap' => [
                                'RESOLVED_VAR_5' => 'ENV_VAR_5',
                            ],
                        ]
                    ),
                ],
                $environmentsDefinitions[1]->getArgument('$adapters')
            );
        }

        /**
         * @param mixed $defaultValue
         */
        private function configurationDefinition(
            string $name,
            bool $required,
            bool $hasDefaultValue,
            $defaultValue
        ): Definition {
            return new Definition(
                EntryConfiguration::class,
                [
                    '$name' => $name,
                    '$required' => $required,
                    '$hasDefaultValue' => $hasDefaultValue,
                    '$defaultValue' => $defaultValue,
                ]
            );
        }

        public function testShouldNotLoadAdapters(): void
        {
            // given
            $container = new ContainerBuilder();

            // when
            $this->extension->load(
                [
                    array_merge(
                        $this->minimalConfiguration(),
                        ['adapters' => []]
                    ),
                ],
                $container
            );

            // then
            $this->assertFalse($container->hasParameter('pk.config.aws.ssm_client.class'));
            $this->assertFalse($container->hasAlias(LocalEnv::class));
        }

        public function testShouldLoadAsStandalone(): void
        {
            // given
            $container = new ContainerBuilder();

            // when
            $this->extension->load([$this->minimalConfiguration()], $container);

            // then
            $this->assertEquals('', $container->getParameter('pk.config.command.prefix'));
        }

        public function testShouldLoadAsBundle(): void
        {
            // given
            $container = new ContainerBuilder();
            $container->setParameter('kernel.bundles', [PKConfigBundle::class]);

            // when
            $this->extension->load([$this->minimalConfiguration()], $container);

            // then
            $this->assertEquals('pk:config:', $container->getParameter('pk.config.command.prefix'));
        }

        /**
         * @return mixed[]
         */
        private function minimalConfiguration(): array
        {
            return [
                'envs' => [
                    'dev' => [
                        'adapters' => [
                            'local',
                        ],
                        'entries' => [
                            'ENV_VAR' => [],
                        ],
                    ],
                ],
            ];
        }

        public function testShouldThrowExceptionWhenAwsSDKNotInstalled(): void
        {
            // given
            self::$mockClassExists = true;
            $configuration = [
                'envs' => [
                    'dev' => [
                        'adapters' => ['aws_ssm'],
                        'entries' => [
                            'ENV_VAR' => [],
                        ],
                    ],
                ],
                'adapters' => [
                    'aws_ssm' => [
                        'client' => [
                            'credentials' => [
                                'key' => 'key',
                                'secret' => 'secret',
                            ],
                            'version' => 'latest',
                            'region' => 'EU',
                        ],
                        'path' => '/path',
                    ],
                ],
            ];
            $container = new ContainerBuilder();
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('composer require aws/aws-sdk-php');

            // when
            $this->extension->load([$configuration], $container);
        }
    }
}
