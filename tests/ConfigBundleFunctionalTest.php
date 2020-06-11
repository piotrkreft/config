<?php

declare(strict_types=1);

namespace PK\Tests\Config;

use PHPUnit\Framework\TestCase;
use PK\Config\Entry;
use PK\Tests\Config\Fixtures\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Filesystem\Filesystem;

class ConfigBundleFunctionalTest extends TestCase
{
    /**
     * @var Kernel
     */
    private $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel('test', false);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->kernel->getCacheDir());
        $this->kernel->shutdown();
    }

    public function testShouldResetTheServiceOnEveryButFirstRequest(): void
    {
        // given
        $config = $this->kernel->getContainer()->get('pk.config');

        // when
        $entries = $config->fetch('dev');

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
        // given
        $application = new Application(
            $this->kernel
        );
        $application->setAutoExit(false);
        $tester = new ApplicationTester(
            $application
        );

        // when
        $exitCode = $tester->run([
            'command' => 'pk:config:validate',
            'env' => 'dev',
        ]);

        // then
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Configuration valid', $tester->getDisplay());
    }

    public function testShouldDisplay(): void
    {
        // given
        $application = new Application(
            $this->kernel
        );
        $application->setAutoExit(false);
        $tester = new ApplicationTester(
            $application
        );

        // when
        $exitCode = $tester->run([
            'command' => 'pk:config:display',
            'env' => 'dev',
        ]);

        // then
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('VAR_2 value_2_ssm', $tester->getDisplay());
        $this->assertStringContainsString('VAR_1 value_1_dummy', $tester->getDisplay());
    }
}
