<?php

declare(strict_types=1);

namespace PK\Tests\Config\StorageAdapter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Entry;
use PK\Config\StorageAdapter\NameResolver;
use PK\Config\StorageAdapterInterface;

class NameResolverTest extends TestCase
{
    /**
     * @var StorageAdapterInterface|MockObject
     */
    private $mockAdapter;

    /**
     * @var NameResolver
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->mockAdapter = $this->createMock(StorageAdapterInterface::class);

        $this->adapter = new NameResolver(
            $this->mockAdapter,
            [
                'name_to_resolve' => 'resolved',
            ]
        );
    }

    public function testShouldResolveName(): void
    {
        // given
        $this->mockAdapter
            ->method('fetch')
            ->willReturn([
                new Entry('name_to_resolve', 'value_1'),
                new Entry('as_should_be', 'value_2'),
            ]);

        // when
        $entries = $this->adapter->fetch('dev');

        // then
        $this->assertEquals(
            [
                new Entry('resolved', 'value_1'),
                new Entry('as_should_be', 'value_2'),
            ],
            $entries
        );
    }
}
