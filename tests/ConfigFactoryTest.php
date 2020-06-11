<?php

declare(strict_types=1);

namespace PK\Tests\Config;

use PHPUnit\Framework\TestCase;
use PK\Config\ConfigFactory;
use PK\Config\ConfigInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigFactoryTest extends TestCase
{
    public function testShouldThrowExceptionForInvalidConfiguration(): void
    {
        // given
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(ConfigInterface::class);

        // when
        ConfigFactory::create(realpath(__DIR__ . '/Fixtures/Resources/config/config_invalid.yaml'));
    }
}
