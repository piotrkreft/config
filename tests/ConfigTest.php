<?php

declare(strict_types=1);

namespace PK\Tests\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Config;
use PK\Config\Entry;
use PK\Config\Environment\EnvironmentInterface;
use PK\Config\Exception\OutOfRangeException;

class ConfigTest extends TestCase
{
    /**
     * @var EnvironmentInterface|MockObject
     */
    private $mockEnvironment;

    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->mockEnvironment = $this->createMock(EnvironmentInterface::class);
        $this->mockEnvironment
            ->method('getName')
            ->willReturn('dev');

        $this->config = new Config(
            [$this->mockEnvironment]
        );
    }

    public function testShouldFetch(): void
    {
        // given
        $environmentEntries = [
            new Entry('VAR', 'value'),
        ];
        $this->mockEnvironment
            ->method('fetch')
            ->willReturn($environmentEntries);

        // when
        $entries = $this->config->fetch('dev');

        // then
        $this->assertSame($entries, $environmentEntries);
    }

    public function testShouldValidate(): void
    {
        // given
        $missingEntries = ['VAR'];
        $this->mockEnvironment
            ->method('validate')
            ->willReturn($missingEntries);

        // when
        $missing = $this->config->validate('dev');

        // then
        $this->assertSame($missing, $missingEntries);
    }

    public function testShouldThrowExceptionWhenEnvironmentMissingForFetch(): void
    {
        // given
        $this->expectException(OutOfRangeException::class);

        // when
        $this->config->fetch('prod');
    }

    public function testShouldThrowExceptionWhenEnvironmentMissingForValidate(): void
    {
        // given
        $this->expectException(OutOfRangeException::class);

        // when
        $this->config->validate('prod');
    }
}
