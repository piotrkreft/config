<?php

declare(strict_types=1);

namespace PK\Config\StorageAdapter;

use PK\Config\Entry;
use PK\Config\StorageAdapterInterface;

class LocalEnv implements StorageAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        $entries = [];
        foreach ($_SERVER + $_ENV as $name => $value) {
            $entries[] = new Entry($name, $value);
        }

        return $entries;
    }
}
