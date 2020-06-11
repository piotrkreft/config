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
    use PK\Config\StorageAdapter\AwsSsm;
    use PK\Config\StorageAdapter\LocalEnv;
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
                                'resolve_from' => 'RESOLVED_ENV_VAR_3',
                            ],
                            'ENV_VAR_4' => [
                                'disabled' => true,
                                'description' => 'Might be useful',
                            ],
                            'ENV_VAR_5' => [],
                        ],
                    ],
                ],
                'entries' => [
                    'ENV_VAR_1' => [],
                    'ENV_VAR_2' => [
                        'default_value' => 'var_2_default',
                        'resolve_from' => 'some_var',
                    ],
                    'ENV_VAR_3' => [
                        'required' => false,
                    ],
                    'ENV_VAR_4' => [
                        'resolve_from' => 'RESOLVED_ENV_VAR_4',
                    ],
                ],
                'adapters' => [
                    'local_env' => [
                        'enabled' => true,
                    ],
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
                        'enabled' => true,
                    ],
                ],
            ];
            $container = new ContainerBuilder();

            // when
            $this->extension->load([$configuration], $container);

            // then
            $this->assertTrue($container->hasDefinition('pk.config.aws.ssm_client'));
            $this->assertTrue($container->hasAlias(AwsSsm::class));
            $this->assertTrue($container->hasAlias(LocalEnv::class));
            $environmentsDefinitions = $container->getDefinition('pk.config')->getArgument('$environments');
            $this->assertCount(1, $environmentsDefinitions);
            $this->assertEquals('dev', $environmentsDefinitions[0]->getArgument('$name'));
            $this->assertEquals(
                [
                    new Definition(
                        EntryConfiguration::class,
                        [
                            '$name' => 'ENV_VAR_1',
                            '$required' => true,
                            '$hasDefaultValue' => false,
                            '$defaultValue' => null,
                            '$resolveFrom' => null,
                        ]
                    ),
                    new Definition(
                        EntryConfiguration::class,
                        [
                            '$name' => 'ENV_VAR_2',
                            '$required' => true,
                            '$hasDefaultValue' => true,
                            '$defaultValue' => 'var_2_default',
                            '$resolveFrom' => 'some_var',
                        ]
                    ),
                    new Definition(
                        EntryConfiguration::class,
                        [
                            '$name' => 'ENV_VAR_3',
                            '$required' => true,
                            '$hasDefaultValue' => false,
                            '$defaultValue' => null,
                            '$resolveFrom' => 'RESOLVED_ENV_VAR_3',
                        ]
                    ),
                    new Definition(
                        EntryConfiguration::class,
                        [
                            '$name' => 'ENV_VAR_5',
                            '$required' => true,
                            '$hasDefaultValue' => false,
                            '$defaultValue' => null,
                            '$resolveFrom' => null,
                        ]
                    ),
                ],
                $environmentsDefinitions[0]->getArgument('$entriesConfiguration')
            );
            $this->assertEquals(
                [
                    new Reference('pk.config.adapter.ssm_client'),
                    new Reference('pk.config.adapter.local_env'),
                    new Reference('custom_service_id'),
                ],
                $environmentsDefinitions[0]->getArgument('$adapters')
            );
        }

        public function testShouldNotLoadAdapters(): void
        {
            // given
            $container = new ContainerBuilder();

            // when
            $this->extension->load([$this->minimalConfiguration()], $container);

            // then
            $this->assertFalse($container->hasDefinition('pk.config.aws.ssm_client'));
            $this->assertFalse($container->hasAlias(AwsSsm::class));
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
                'adapters' => [],
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
                        'enabled' => true,
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
