<?php

declare(strict_types=1);

namespace PK\Config\Environment;

use PK\Config\Entry;
use PK\Config\Exception\InvalidArgumentException;
use PK\Config\Exception\LogicException;
use PK\Config\Exception\MissingValuesException;
use PK\Config\StorageAdapterInterface;

class Environment implements EnvironmentInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var StorageAdapterInterface[]|iterable
     */
    private $adapters;

    /**
     * @var EntryConfiguration[]
     */
    private $requiredEntries;

    /**
     * @var EntryConfiguration[]
     */
    private $optionalEntries;

    /**
     * @var EntryConfiguration[]
     */
    private $entries;

    /**
     * @param StorageAdapterInterface[] $adapters
     * @param EntryConfiguration[]      $entriesConfiguration
     */
    public function __construct(string $name, iterable $adapters, iterable $entriesConfiguration)
    {
        $this->name = $name;
        if (!count($adapters)) {
            throw new LogicException('At least one adapter has to be provided.');
        }
        if (!count($entriesConfiguration)) {
            throw new LogicException('At least one entry configuration has to be provided.');
        }
        foreach ($adapters as $adapter) {
            if (!$adapter instanceof StorageAdapterInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Adapter should implement %s. %s Given.',
                    StorageAdapterInterface::class,
                    get_debug_type($adapter)
                ));
            }
        }
        $this->adapters = $adapters;
        $this->requiredEntries = $this->optionalEntries = [];
        foreach ($entriesConfiguration as $configuration) {
            if (!$configuration instanceof EntryConfiguration) {
                throw new InvalidArgumentException(sprintf(
                    'Entry configuration should implement %s. %s Given.',
                    EntryConfiguration::class,
                    is_object($configuration) ? get_class($configuration) : gettype($configuration)
                ));
            }
            $property = $configuration->isRequired() ? 'requiredEntries' : 'optionalEntries';
            $this->{$property}[$configuration->getName()] = $this->entries[$configuration->getName()] = $configuration;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(): array
    {
        $entries = $this->doFetch();

        if ($missing = $this->validateMissing($entries)) {
            throw new MissingValuesException($this->getName(), ...$missing);
        }

        return array_values(
            array_merge(
                array_intersect_key($entries, $this->optionalEntries),
                array_intersect_key($entries, $this->requiredEntries)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): array
    {
        $entries = $this->doFetch();

        return $this->validateMissing($entries);
    }

    /**
     * @return Entry[]
     */
    private function doFetch(): array
    {
        $entries = [];

        foreach ($this->adapters as $adapter) {
            $fetched = $adapter->fetch($this->name);
            foreach ($fetched as $entry) {
                if ($entries[$entry->getName()] ?? null) {
                    continue;
                }
                $entries[$entry->getName()] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param Entry[] $entries
     *
     * @return string[]
     */
    private function validateMissing(array &$entries): array
    {
        if (!$missing = array_diff_key($this->requiredEntries, $entries)) {
            return [];
        }
        $withNoDefault = [];
        foreach (array_keys($missing) as $miss) {
            if ($this->requiredEntries[$miss]->hasDefaultValue()) {
                $entries[$miss] = new Entry($miss, $this->requiredEntries[$miss]->getDefaultValue());
                continue;
            }
            $withNoDefault[] = $this->requiredEntries[$miss]->getName();
        }

        return $withNoDefault;
    }
}
