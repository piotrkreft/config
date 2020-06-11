<?php

declare(strict_types=1);

namespace PK\Config\Environment;

use PK\Config\Entry;
use PK\Config\Exception\MissingValuesException;
use PK\Config\Exception\RuntimeException;

interface EnvironmentInterface
{
    public function getName(): string;

    /**
     * @throws MissingValuesException
     * @throws RuntimeException
     *
     * @return Entry[]
     */
    public function fetch(): array;

    /**
     * @throws RuntimeException
     *
     * @return string[]
     */
    public function validate(): array;
}
