<?php

declare(strict_types=1);

namespace PK\Config\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;

interface ContainerFactoryInterface
{
    public function create(?string $configurationFile = null): ContainerInterface;
}
