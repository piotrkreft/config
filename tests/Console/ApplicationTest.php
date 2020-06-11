<?php

declare(strict_types=1);

namespace PK\Tests\Config\Console;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Console\Application;
use PK\Config\DependencyInjection\ContainerFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApplicationTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $mockContainer;

    /**
     * @var ContainerFactory|MockObject
     */
    private $mockContainerFactory;

    /**
     * @var Application
     */
    private $application;

    protected function setUp(): void
    {
        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->mockContainerFactory = $this->createMock(ContainerFactory::class);

        $this->application = new Application(
            $this->mockContainerFactory
        );

        $this->mockContainerFactory
            ->method('create')
            ->willReturn($this->mockContainer);
    }

    public function testShouldGetCommand(): void
    {
        // given
        $command = new Command('custom');
        $this->mockLoader($command);

        // when
        $result = $this->application->get('custom');

        // then
        $this->assertSame($command, $result);
    }

    public function testShouldFindCommand(): void
    {
        // given
        $command = new Command('custom');
        $this->mockLoader($command);

        // when
        $result = $this->application->find('custom');

        // then
        $this->assertSame($command, $result);
    }

    public function testShouldFindAll(): void
    {
        // given
        $command = new Command('custom');
        $this->mockLoader($command);

        // when
        $commands = $this->application->all();

        // then
        $this->assertEquals(3, count($commands));
    }

    private function mockLoader(Command $command): void
    {
        $loader = $this->createMock(CommandLoaderInterface::class);
        $loader
            ->method('getNames')
            ->willReturn([$command->getName()]);
        $loader
            ->method('has')
            ->willReturn(true);
        $loader
            ->method('get')
            ->willReturn($command);
        $this->mockContainer
            ->method('get')
            ->with('console.command_loader')
            ->willReturn($loader);
    }

    public function testShouldThrowExceptionWhenInvalidCommandLoaderInContainer(): void
    {
        // given
        $this->mockContainer
            ->method('get')
            ->with('console.command_loader')
            ->willReturn(null);
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Command loader should be instance of ' . CommandLoaderInterface::class);

        // when
        $this->application->get('command');
    }
}
