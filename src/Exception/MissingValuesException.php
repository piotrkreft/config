<?php

declare(strict_types=1);

namespace PK\Config\Exception;

class MissingValuesException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @var string[]
     */
    private $missingVars;

    public function __construct(string $environment, string ...$missingVars)
    {
        $this->missingVars = $missingVars;
        parent::__construct(
            "Missing vars for $environment: [" . implode(', ', $missingVars) . ']'
        );
    }

    /**
     * @return string[]
     */
    public function getMissingVars(): array
    {
        return $this->missingVars;
    }
}
