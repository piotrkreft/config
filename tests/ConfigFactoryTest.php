<?php

declare(strict_types=1);

namespace PK\Tests\Config;

use PHPUnit\Framework\TestCase;
use PK\Config\ConfigFactory;
use PK\Config\ConfigInterface;
use PK\Config\DependencyInjection\ContainerFactoryInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigFactoryTest extends TestCase
{
    public function testShouldThrowExceptionForInvalidConfiguration(): void
    {
        // given
        $containerFactory = $this->createMock(ContainerFactoryInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $containerFactory->method('create')->willReturn($container);
        $container->method('get')->willReturn(new \stdClass());

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(ConfigInterface::class);

        // when
        ConfigFactory::create('path', $containerFactory);
    }

    public function testShouldUseProvidedFactory(): void
    {
        // given
        $containerFactory = $this->createMock(ContainerFactoryInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $containerFactory->method('create')->willReturn($container);
        $config = $this->createMock(ConfigInterface::class);
        $container->method('get')->willReturn($config);

        // when
        $actual = ConfigFactory::create('path', $containerFactory);

        // then
        $this->assertSame($config, $actual);
    }
}
