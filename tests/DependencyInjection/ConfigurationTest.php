<?php

declare(strict_types=1);

namespace PK\Tests\Config\DependencyInjection;

use PHPUnit\Framework\TestCase;
use PK\Config\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Configuration
     */
    private $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();

        $this->configuration = new Configuration();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testShouldCreateConfigurationPerEnv(): void
    {
        // given
        $configuration = [
            'envs' => [
                'dev' => [
                    'adapters' => [
                        'aws_ssm',
                        'local',
                    ],
                    'entries' => [
                        'ENV_VAR_1' => [
                            'disabled' => true,
                        ],
                        'ENV_VAR_2' => [
                            'default_value' => 2,
                            'resolve_from' => [
                                'aws_ssm' => 'some_var',
                                'local' => 'some_var',
                            ],
                        ],
                        'ENV_VAR_3' => [
                            'required' => false,
                            'resolve_from' => 'env_var_3',
                        ],
                        'ENV_VAR_4' => [
                            'description' => 'Might be useful',
                        ],
                    ],
                ],
                'prod' => [
                    'adapters' => 'aws_ssm',
                    'entries' => [
                        'ENV_VAR_1' => null,
                    ],
                ],
            ],
            'entries' => [
                'ENV_VAR_1' => [],
                'ENV_VAR_2' => [
                    'required' => true,
                    'default_value' => 2,
                    'resolve_from' => 'some_var',
                ],
                'ENV_VAR_3' => [
                    'required' => false,
                ],
            ],
            'adapters' => [
                'local_env' => null,
                'aws_ssm' => [
                    'client' => [
                        'credentials' => [
                            'key' => 'key',
                            'secret' => 'secret',
                        ],
                        'region' => 'EU',
                    ],
                    'path' => '/path',
                ],
            ],
        ];

        // when
        $normalized = $this->processor->processConfiguration(
            $this->configuration,
            ['pk_config' => $configuration]
        );

        // then
        $this->assertEquals(
            [
                'envs' => [
                    'dev' => [
                        'adapters' => [
                            'aws_ssm',
                            'local',
                        ],
                        'entries' => [
                            'ENV_VAR_2' => [
                                'required' => true,
                                'default_value' => 2,
                                'resolve_from' => [
                                    'aws_ssm' => 'some_var',
                                    'local' => 'some_var',
                                ],
                                'disabled' => false,
                            ],
                            'ENV_VAR_3' => [
                                'required' => false,
                                'resolve_from' => [
                                    'aws_ssm' => 'env_var_3',
                                    'local' => 'env_var_3',
                                ],
                                'disabled' => false,
                            ],
                            'ENV_VAR_4' => [
                                'description' => 'Might be useful',
                                'required' => true,
                                'resolve_from' => [],
                                'disabled' => false,
                            ],
                        ],
                    ],
                    'prod' => [
                        'adapters' => [
                            'aws_ssm',
                        ],
                        'entries' => [
                            'ENV_VAR_1' => [
                                'disabled' => false,
                                'required' => true,
                                'resolve_from' => [],
                            ],
                            'ENV_VAR_2' => [
                                'required' => true,
                                'default_value' => 2,
                                'resolve_from' => [
                                    'aws_ssm' => 'some_var',
                                ],
                            ],
                            'ENV_VAR_3' => [
                                'required' => false,
                                'resolve_from' => [],
                            ],
                        ],
                    ],
                ],
                'adapters' => [
                    'local_env' => [
                        'enabled' => true,
                    ],
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
                    ],
                ],
            ],
            $normalized
        );
    }

    /**
     * @dataProvider defaultValueProvider
     *
     * @param mixed $defaultValue
     */
    public function testShouldThrowExceptionForNotRequiredAndDefaultValue($defaultValue): void
    {
        $configuration = [
            'envs' => [
                'dev' => [
                    'adapters' => [
                        'aws_ssm',
                        'local',
                    ],
                ],
            ],
            'entries' => [
                'ENV_VAR_1' => [
                    'required' => false,
                    'default_value' => $defaultValue,
                ],
            ],
        ];
        $this->expectExceptionMessage('Cannot set `required` as false and `default_value`.');

        $this->processor->processConfiguration(
            $this->configuration,
            ['pk_config' => $configuration]
        );
    }

    /**
     * @return mixed[][]
     */
    public function defaultValueProvider(): array
    {
        return [
            ['value'],
            [null],
            [[]],
        ];
    }

    public function testShouldThrowExceptionForNotExistingAdapterInResolveFromEntry(): void
    {
        $configuration = [
            'envs' => [
                'dev' => [
                    'adapters' => [
                        'aws_ssm',
                        'local',
                    ],
                    'entries' => [
                        'VAR_2' => [
                            'resolve_from' => [
                                'non_existing' => 'name_to_resolve',
                            ],
                        ],
                    ],
                ],
            ],
            'entries' => [
                'VAR_1' => [
                    'required' => false,
                ],
            ],
        ];
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path \'pk_config.envs.dev.entries.VAR_2.resolve_from\':'
            . ' \'non_existing\' adapter not configured for environment.'
        );

        $this->processor->processConfiguration(
            $this->configuration,
            ['pk_config' => $configuration]
        );
    }
}
