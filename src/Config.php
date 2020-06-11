<?php

declare(strict_types=1);

namespace PK\Config;

use PK\Config\Environment\EnvironmentInterface;
use PK\Config\Exception\OutOfRangeException;

class Config implements ConfigInterface
{
    /**
     * @var EnvironmentInterface[]
     */
    private $environments;

    /**
     * @param EnvironmentInterface[] $environments
     */
    public function __construct(iterable $environments)
    {
        $this->environments = [];
        foreach ($environments as $environment) {
            $this->environments[$environment->getName()] = $environment;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        $env = $this->getEnvironment($environment);

        return $env->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $environment): array
    {
        $env = $this->getEnvironment($environment);

        return $env->validate();
    }

    private function getEnvironment(string $environment): EnvironmentInterface
    {
        if (!$env = $this->environments[$environment] ?? null) {
            throw new OutOfRangeException("$environment is not a configured environment.");
        }

        return $env;
    }
}
