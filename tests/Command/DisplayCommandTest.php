<?php

declare(strict_types=1);

namespace PK\Tests\Config\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Command\DisplayCommand;
use PK\Config\ConfigInterface;
use PK\Config\Entry;
use PK\Config\Exception\ExceptionInterface;
use Symfony\Component\Console\Tester\CommandTester;

class DisplayCommandTest extends TestCase
{
    /**
     * @var ConfigInterface|MockObject
     */
    private $mockConfiguration;

    /**
     * @var DisplayCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $tester;

    protected function setUp(): void
    {
        $this->mockConfiguration = $this->createMock(ConfigInterface::class);

        $this->command = new DisplayCommand(
            $this->mockConfiguration
        );

        $this->tester = new CommandTester(
            $this->command
        );
    }

    public function testShouldFetch(): void
    {
        // given
        $this->mockConfiguration
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'value_1'),
                new Entry('VAR_2', 'value_2'),
            ]);

        // when
        $exitCode = $this->tester->execute(['env' => 'dev']);

        // then
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('VAR_1 value_1', $this->tester->getDisplay());
        $this->assertStringContainsString('VAR_2 value_2', $this->tester->getDisplay());
    }

    public function testShouldReturnErrorCodeWhenFetchingThrowsException(): void
    {
        // given
        $exception = new class ('Exception message') extends \Exception implements ExceptionInterface {
        };
        $this->mockConfiguration
            ->method('fetch')
            ->willThrowException($exception);

        // when
        $exitCode = $this->tester->execute(['env' => 'dev']);

        // then
        $this->assertEquals(2, $exitCode);
        $this->assertStringContainsString('Exception message', $this->tester->getDisplay());
    }

    public function testShouldReturnErrorCodeWhenMissingArgument(): void
    {
        // when
        $exitCode = $this->tester->execute([]);

        // then
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('env argument missing', $this->tester->getDisplay());
    }
}
