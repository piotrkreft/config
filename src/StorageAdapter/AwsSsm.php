<?php

declare(strict_types=1);

namespace PK\Config\StorageAdapter;

use Aws\Result;
use Aws\Ssm\SsmClient;
use PK\Config\Entry;
use PK\Config\StorageAdapterInterface;

class AwsSsm implements StorageAdapterInterface
{
    /**
     * @var SsmClient
     */
    private $ssmClient;

    /**
     * @var string|null
     */
    private $path;

    public function __construct(SsmClient $ssmClient, ?string $path)
    {
        $this->ssmClient = $ssmClient;
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        $path = str_replace('{env}', $environment, $this->path);

        $entries = [];
        $parameters = $nextToken = null;
        while (!$parameters || $nextToken) {
            $parameters = $this->doFetch($environment, $nextToken);

            foreach ($parameters['Parameters'] as $parameter) {
                $name = str_replace($path, '', $parameter['Name']);
                $entries[] = new Entry($name, $parameter['Value']);
            }
            $nextToken = $parameters['NextToken'] ?? null;
        }

        return $entries;
    }

    private function doFetch(string $environment, ?string $nextToken): Result
    {
        if (!$this->path) {
            return $this->ssmClient->getParameters(
                array_filter([
                    'WithDecryption' => true,
                    'NextToken' => $nextToken,
                ])
            );
        }

        $path = str_replace('{env}', $environment, $this->path);

        return $this->ssmClient->getParametersByPath(
            array_filter([
                'Path' => $path,
                'WithDecryption' => true,
                'NextToken' => $nextToken,
            ])
        );
    }
}
