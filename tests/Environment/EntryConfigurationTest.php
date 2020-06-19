<?php

declare(strict_types=1);

namespace PK\Tests\Config\Environment;

use PHPUnit\Framework\TestCase;
use PK\Config\Environment\EntryConfiguration;
use PK\Config\Exception\LogicException;

class EntryConfigurationTest extends TestCase
{
    public function testShouldReturnDefaultValueWhenProvided(): void
    {
        // given
        $entry = new EntryConfiguration('name', true, true, 'value');

        // when
        $defaultValue = $entry->getDefaultValue();

        // then
        $this->assertEquals('value', $defaultValue);
    }

    public function testShouldThrowExceptionWhenNoDefaultValue(): void
    {
        // given
        $entry = new EntryConfiguration('name');
        $this->expectException(LogicException::class);

        // when
        $entry->getDefaultValue();
    }

    public function testShouldHaveDefaultValues(): void
    {
        // when
        $entry = new EntryConfiguration('name');

        // then
        $this->assertTrue($entry->isRequired());
        $this->assertFalse($entry->hasDefaultValue());
    }
}
