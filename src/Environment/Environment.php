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
            $this->validateElement($adapter, StorageAdapterInterface::class, 'Adapter');
        }
        $this->adapters = $adapters;
        $this->requiredEntries = $this->optionalEntries = [];
        foreach ($entriesConfiguration as $configuration) {
            $this->validateElement($configuration, EntryConfiguration::class, 'Entry configuration');
            $property = $configuration->isRequired() ? 'requiredEntries' : 'optionalEntries';
            $this->{$property}[$configuration->getName()] = $this->entries[$configuration->getName()] = $configuration;
        }
    }

    /**
     * @param mixed $element
     */
    private function validateElement($element, string $expectedType, string $name): void
    {
        if ($element instanceof $expectedType) {
            return;
        }
        $type = get_debug_type($element);

        throw new InvalidArgumentException("$name should implement $expectedType. $type Given.");
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
