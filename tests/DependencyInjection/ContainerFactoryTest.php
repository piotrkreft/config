<?php

declare(strict_types=1);

namespace PK\Tests\Config\DependencyInjection;

use PHPUnit\Framework\TestCase;
use PK\Config\DependencyInjection\ContainerFactory;

class ContainerFactoryTest extends TestCase
{
    /**
     * @var ContainerFactory
     */
    private $containerFactory;

    protected function setUp(): void
    {
        $this->containerFactory = new ContainerFactory();
    }

    public function testShouldCreate(): void
    {
        // given
        $this->expectNotToPerformAssertions();

        // when
        $this->containerFactory->create(realpath(__DIR__ . '/../Fixtures/Resources/config/config.yaml'));
    }

    public function testShouldCreateWithoutConfigurationFile(): void
    {
        // given
        $this->expectNotToPerformAssertions();

        // when
        $this->containerFactory->create();
    }
}
