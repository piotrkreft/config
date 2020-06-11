<?php

declare(strict_types=1);

namespace PK\Tests\Config\Environment;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Entry;
use PK\Config\Environment\EntryConfiguration;
use PK\Config\Environment\Environment;
use PK\Config\Exception\InvalidArgumentException;
use PK\Config\Exception\LogicException;
use PK\Config\Exception\MissingValuesException;
use PK\Config\StorageAdapterInterface;

class EnvironmentTest extends TestCase
{
    /**
     * @var StorageAdapterInterface|MockObject
     */
    private $firstAdapter;

    /**
     * @var StorageAdapterInterface|MockObject
     */
    private $secondAdapter;

    /**
     * @var Environment
     */
    private $environment;

    protected function setUp(): void
    {
        $this->firstAdapter = $this->createMock(StorageAdapterInterface::class);
        $this->secondAdapter = $this->createMock(StorageAdapterInterface::class);

        $this->environment = new Environment(
            'env',
            [
                $this->firstAdapter,
                $this->secondAdapter,
            ],
            [
                new EntryConfiguration('VAR_1', true, false, null, null),
                new EntryConfiguration('VAR_2', true, true, 'default_value', null),
                new EntryConfiguration('VAR_3', true, false, null, 'VAR_3{origin_name}'),
                new EntryConfiguration('VAR_4', false, false, null, null),
                new EntryConfiguration('VAR_5', false, false, null, null),
            ]
        );
    }

    public function testShouldFetchEntries(): void
    {
        // given
        $this->firstAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'VAR_1_value_first'),
                new Entry('VAR_3{origin_name}', 'VAR_3_value_first'),
                new Entry('VAR_4', 'VAR_4_value_first'),
                new Entry('VAR_5', 'VAR_5_value_first'),
                new Entry('VAR_6', 'VAR_6_value_first'),
            ]);
        $this->secondAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'VAR_1_value_second'),
                new Entry('VAR_2', 'VAR_2_value_second'),
                new Entry('VAR_3', 'VAR_3_value_second'),
                new Entry('VAR_7', 'VAR_7_value_second'),
            ]);

        // when
        $entries = $this->environment->fetch();

        // then
        $this->assertEquals(
            [
                new Entry('VAR_4', 'VAR_4_value_first'),
                new Entry('VAR_5', 'VAR_5_value_first'),
                new Entry('VAR_1', 'VAR_1_value_first'),
                new Entry('VAR_3', 'VAR_3_value_first'),
                new Entry('VAR_2', 'VAR_2_value_second'),
            ],
            $entries
        );
    }

    public function testShouldFetchEntriesWhenDefaultMissing(): void
    {
        // given
        $this->firstAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'VAR_1_value_first'),
                new Entry('VAR_3{origin_name}', 'VAR_3_value_first'),
                new Entry('VAR_4', 'VAR_4_value_first'),
                new Entry('VAR_6', 'VAR_6_value_first'),
            ]);
        $this->secondAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'VAR_1_value_second'),
                new Entry('VAR_3', 'VAR_3_value_second'),
                new Entry('VAR_7', 'VAR_7_value_second'),
            ]);

        // when
        $entries = $this->environment->fetch();

        // then
        $this->assertEquals(
            [
                new Entry('VAR_4', 'VAR_4_value_first'),
                new Entry('VAR_1', 'VAR_1_value_first'),
                new Entry('VAR_3', 'VAR_3_value_first'),
                new Entry('VAR_2', 'default_value'),
            ],
            $entries
        );
    }

    public function testShouldThrowExceptionForMissingVariables(): void
    {
        // given
        $this->firstAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_5', 'VAR_5_value_first'),
            ]);
        $this->secondAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_6', 'VAR_6_value_second'),
            ]);

        // when
        $caught = false;
        try {
            $this->environment->fetch();
        } catch (MissingValuesException $exception) {
            $caught = true;
        }

        // then
        $this->assertTrue($caught);
        $this->assertEquals(
            [
                'VAR_1',
                'VAR_3',
            ],
            $exception->getMissingVars()
        );
        $this->assertStringContainsString('VAR_1, VAR_3', $exception->getMessage());
    }

    public function testShouldReturnNoInvalidEntries(): void
    {
        // given
        $this->firstAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'VAR_1_value_first'),
                new Entry('VAR_3{origin_name}', 'VAR_3_value_first'),
                new Entry('VAR_6', 'VAR_6_value_second'),
            ]);
        $this->secondAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_1', 'VAR_1_value_second'),
                new Entry('VAR_3', 'VAR_3_value_second'),
                new Entry('VAR_7', 'VAR_7_value_second'),
            ]);

        // when
        $missing = $this->environment->validate();

        // then
        $this->assertEmpty($missing);
    }

    public function testShouldReturnMissingVariables(): void
    {
        // given
        $this->firstAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_5', 'VAR_5_value_first'),
            ]);
        $this->secondAdapter
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                new Entry('VAR_6', 'VAR_6_value_second'),
            ]);

        // when
        $missing = $this->environment->validate();

        // then
        $this->assertEquals(['VAR_1', 'VAR_3'], $missing);
    }

    public function testShouldThrowExceptionForEmptyAdapters(): void
    {
        // given
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('adapter');

        // when
        new Environment('env', new \ArrayObject(), new \ArrayObject());
    }

    public function testShouldThrowExceptionForEmptyEntriesConfiguration(): void
    {
        // given
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('entry configuration');

        // when
        new Environment('env', [$this->firstAdapter], new \ArrayObject());
    }

    public function testShouldThrowExceptionWhenNoAdapter(): void
    {
        // given
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(StorageAdapterInterface::class);

        // when
        new Environment('env', [new \stdClass()], [new EntryConfiguration('name')]);
    }

    public function testShouldThrowExceptionWhenNoEntryConfiguration(): void
    {
        // given
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(EntryConfiguration::class);

        // when
        new Environment('env', [$this->firstAdapter], [new \stdClass()]);
    }
}
