<?php

declare(strict_types=1);

namespace PK\Config;

use PK\Config\Exception\RuntimeException;

interface StorageAdapterInterface
{
    /**
     * @throws RuntimeException
     *
     * @return Entry[]
     */
    public function fetch(string $environment): array;
}
