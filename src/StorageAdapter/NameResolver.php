<?php

declare(strict_types=1);

namespace PK\Config\StorageAdapter;

use PK\Config\Entry;
use PK\Config\StorageAdapterInterface;

class NameResolver implements StorageAdapterInterface
{
    /**
     * @var StorageAdapterInterface
     */
    private $adapter;

    /**
     * @var string[]
     */
    private $resolveFromMap;

    /**
     * @param string[] $resolveFromMap
     */
    public function __construct(StorageAdapterInterface $adapter, array $resolveFromMap)
    {
        $this->adapter = $adapter;
        $this->resolveFromMap = $resolveFromMap;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        $entries = $this->adapter->fetch($environment);
        $resolved = [];
        foreach ($entries as $entry) {
            $resolved[] = $this->resolveEntry($entry);
        }

        return $resolved;
    }

    private function resolveEntry(Entry $entry): Entry
    {
        return ($name = $this->resolveFromMap[$entry->getName()] ?? null) ?
            new Entry($name, $entry->getValue()) :
            $entry;
    }
}
