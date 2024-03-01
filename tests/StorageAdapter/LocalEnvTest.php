<?php

declare(strict_types=1);

namespace PK\Tests\Config\StorageAdapter
{
    use PHPUnit\Framework\TestCase;
    use PK\Config\StorageAdapter\LocalEnv;

    class LocalEnvTest extends TestCase
    {
        /**
         * @var LocalEnv
         */
        private $adapter;

        protected function setUp(): void
        {
            $this->adapter = new LocalEnv();
            $_SERVER['ENV_VAR_1'] = 'env_var_value_1';
            $_ENV['ENV_VAR_2'] = 'env_var_value_2';
        }

        public function testShouldFetch(): void
        {
            // when
            $entries = $this->adapter->fetch('some');

            // then
            $foundVar1 = $foundVar2 = false;
            foreach ($entries as $entry) {
                if ('ENV_VAR_1' === $entry->getName()) {
                    $foundVar1 = $entry->getValue();
                }
                if ('ENV_VAR_2' === $entry->getName()) {
                    $foundVar2 = $entry->getValue();
                }
            }
            $this->assertEquals('env_var_value_1', $foundVar1);
            $this->assertEquals('env_var_value_2', $foundVar2);
        }
    }
}
