<?php

declare(strict_types=1);

namespace PK\Tests\Config\Fixtures\StorageAdapter;

use PK\Config\Entry;
use PK\Config\StorageAdapterInterface;

class DummyAdapter implements StorageAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        return [
            new Entry('VAR_1', 'value_1_dummy'),
            new Entry('VAR_2', 'value_2_dummy'),
        ];
    }
}
