<?php

declare(strict_types=1);

namespace PK\Config;

use PK\Config\Exception\ExceptionInterface;

interface ConfigInterface
{
    /**
     * @throws ExceptionInterface
     *
     * @return Entry[]
     */
    public function fetch(string $environment): array;

    /**
     * @throws ExceptionInterface
     *
     * @return string[]
     */
    public function validate(string $environment): array;
}
