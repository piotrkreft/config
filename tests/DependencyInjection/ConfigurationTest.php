<?php

declare(strict_types=1);

namespace PK\Tests\Config\DependencyInjection;

use PHPUnit\Framework\TestCase;
use PK\Config\DependencyInjection\Configuration;
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
                        'ENV_VAR_1' => [],
                        'ENV_VAR_2' => [
                            'default_value' => 2,
                            'resolve_from' => 'some_var',
                        ],
                        'ENV_VAR_3' => [
                            'required' => false,
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
                            'ENV_VAR_1' => [
                                'required' => true,
                                'disabled' => false,
                            ],
                            'ENV_VAR_2' => [
                                'required' => true,
                                'default_value' => 2,
                                'resolve_from' => 'some_var',
                                'disabled' => false,
                            ],
                            'ENV_VAR_3' => [
                                'required' => false,
                                'disabled' => false,
                            ],
                            'ENV_VAR_4' => [
                                'description' => 'Might be useful',
                                'required' => true,
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
                            ],
                        ],
                    ],
                ],
                'entries' => [
                    'ENV_VAR_1' => [
                        'required' => true,
                    ],
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
            ],
            $normalized
        );
    }

    public function testShouldThrowExceptionForNotRequiredAndDefaultValue(): void
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
                    'default_value' => 'value',
                ],
            ],
        ];
        $this->expectExceptionMessage('Cannot set `required` as false and `default_value`.');

        $this->processor->processConfiguration(
            $this->configuration,
            ['pk_config' => $configuration]
        );
    }
}
