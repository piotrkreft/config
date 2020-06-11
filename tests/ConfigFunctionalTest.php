<?php

declare(strict_types=1);

namespace PK\Tests\Config;

use PHPUnit\Framework\TestCase;
use PK\Config\Config;
use PK\Config\ConfigFactory;
use PK\Config\Entry;

class ConfigFunctionalTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->config = ConfigFactory::create(realpath(__DIR__ . '/Fixtures/Resources/config/config.yaml'));
    }

    public function testShouldFetch(): void
    {
        // when
        $entries = $this->config->fetch('dev');

        // then
        $this->assertEquals(
            [
                new Entry('VAR_2', 'value_2_ssm'),
                new Entry('VAR_1', 'value_1_dummy'),
            ],
            $entries
        );
    }

    public function testShouldValidate(): void
    {
        // when
        $invalid = $this->config->validate('dev');

        // then
        $this->assertEquals([], $invalid);
    }
}
