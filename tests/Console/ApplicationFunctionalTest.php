<?php

declare(strict_types=1);

namespace PK\Tests\Config\Console;

use PHPUnit\Framework\TestCase;
use PK\Config\Console\Application;
use PK\Config\DependencyInjection\ContainerFactory;
use Symfony\Component\Console\Tester\ApplicationTester;

class ApplicationFunctionalTest extends TestCase
{
    /**
     * @var ApplicationTester
     */
    private $tester;

    protected function setUp(): void
    {
        $application = new Application(
            new ContainerFactory()
        );
        $application->setAutoExit(false);
        $this->tester = new ApplicationTester(
            $application
        );
    }

    /**
     * @dataProvider inputOptionProvider
     */
    public function testShouldValidate(string $option): void
    {
        // when
        $exitCode = $this->tester->run([
            'command' => 'validate',
            $option => 'tests/Fixtures/Resources/config/config.yaml',
            'env' => 'dev',
        ]);

        // then
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Configuration valid', $this->tester->getDisplay());
    }

    /**
     * @dataProvider inputOptionProvider
     */
    public function testShouldDisplay(string $option): void
    {
        // when
        $exitCode = $this->tester->run([
            'command' => 'display',
            $option => 'tests/Fixtures/Resources/config/config.yaml',
            'env' => 'dev',
        ]);

        // then
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('VAR_2 value_2_ssm', $this->tester->getDisplay());
        $this->assertStringContainsString('VAR_1 value_1_dummy', $this->tester->getDisplay());
    }

    /**
     * @return string[][]
     */
    public function inputOptionProvider(): array
    {
        return [
            ['--configuration'],
            ['-c'],
        ];
    }
}
