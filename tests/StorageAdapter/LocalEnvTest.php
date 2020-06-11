<?php

declare(strict_types=1);

namespace PK\Config\StorageAdapter
{
    use PK\Tests\Config\StorageAdapter\LocalEnvTest;

    /**
     * @return string[]
     */
    function getenv(): array
    {
        if (LocalEnvTest::$mockGetenv) {
            return [
                'ENV_VAR_1' => 'env_var_value_1',
                'ENV_VAR_2' => 'env_var_value_2',
            ];
        }

        return \getenv();
    }
}
namespace PK\Tests\Config\StorageAdapter
{
    use PHPUnit\Framework\TestCase;
    use PK\Config\Entry;
    use PK\Config\StorageAdapter\LocalEnv;

    class LocalEnvTest extends TestCase
    {
        /**
         * @var bool
         */
        public static $mockGetenv = false;

        /**
         * @var LocalEnv
         */
        private $adapter;

        protected function setUp(): void
        {
            $this->adapter = new LocalEnv();
        }

        protected function tearDown(): void
        {
            self::$mockGetenv = false;
        }

        public function testShouldFetch(): void
        {
            // given
            self::$mockGetenv = true;

            // when
            $entries = $this->adapter->fetch('some');

            // then
            $this->assertEquals(
                [
                    new Entry('ENV_VAR_1', 'env_var_value_1'),
                    new Entry('ENV_VAR_2', 'env_var_value_2'),
                ],
                $entries
            );
        }
    }
}
