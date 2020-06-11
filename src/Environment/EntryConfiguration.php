<?php

declare(strict_types=1);

namespace PK\Config\Environment;

use PK\Config\Exception\LogicException;

class EntryConfiguration
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var bool
     */
    private $hasDefaultValue;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var string
     */
    private $resolveFrom;

    /**
     * @param mixed $defaultValue
     */
    public function __construct(
        string $name,
        bool $required = true,
        bool $hasDefaultValue = false,
        $defaultValue = null,
        ?string $resolveFrom = null
    ) {
        $this->name = $name;
        $this->required = $required;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->resolveFrom = $resolveFrom ?? $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        if (!$this->hasDefaultValue) {
            throw new LogicException('Entry has no default value.');
        }

        return $this->defaultValue;
    }

    public function getResolveFrom(): ?string
    {
        return $this->resolveFrom;
    }
}
