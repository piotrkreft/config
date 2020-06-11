<?php

declare(strict_types=1);

namespace PK\Tests\Config\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Command\ValidateCommand;
use PK\Config\ConfigInterface;
use PK\Config\Exception\ExceptionInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateCommandTest extends TestCase
{
    /**
     * @var ConfigInterface|MockObject
     */
    private $mockConfiguration;

    /**
     * @var ValidateCommand
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $tester;

    protected function setUp(): void
    {
        $this->mockConfiguration = $this->createMock(ConfigInterface::class);

        $this->command = new ValidateCommand(
            $this->mockConfiguration
        );

        $this->tester = new CommandTester(
            $this->command
        );
    }

    public function testShouldValidate(): void
    {
        // given
        $this->mockConfiguration
            ->method('validate')
            ->willReturn([]);

        // when
        $exitCode = $this->tester->execute(['env' => 'dev']);

        // then
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Configuration valid', $this->tester->getDisplay());
    }

    public function testShouldReturnErrorCodeWhenValidationFails(): void
    {
        // given
        $this->mockConfiguration
            ->method('validate')
            ->willReturn(['VAR_1', 'VAR_2']);

        // when
        $exitCode = $this->tester->execute(['env' => 'dev']);

        // then
        $this->assertEquals(3, $exitCode);
        $this->assertStringContainsString('Following variables missing', $this->tester->getDisplay());
        $this->assertStringContainsString('VAR_1', $this->tester->getDisplay());
        $this->assertStringContainsString('VAR_2', $this->tester->getDisplay());
    }

    public function testShouldReturnErrorCodeWhenValidationThrowsException(): void
    {
        // given
        $exception = new class ('Exception message') extends \Exception implements ExceptionInterface {
        };
        $this->mockConfiguration
            ->method('validate')
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
