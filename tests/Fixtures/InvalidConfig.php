<?php

declare(strict_types=1);

namespace PK\Tests\Config\Fixtures;

use PK\Config\Environment\EnvironmentInterface;

class InvalidConfig
{
    /**
     * @param EnvironmentInterface[] $environments
     */
    public function __construct(iterable $environments)
    {
    }
}
