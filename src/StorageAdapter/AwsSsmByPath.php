<?php

declare(strict_types=1);

namespace PK\Config\StorageAdapter;

use Aws\Result;
use Aws\Ssm\SsmClient;
use PK\Config\Entry;
use PK\Config\StorageAdapterInterface;

class AwsSsmByPath implements StorageAdapterInterface
{
    private const ENV_PLACEHOLDER = '{env}';

    /**
     * @var SsmClient
     */
    private $ssmClient;

    /**
     * @var string
     */
    private $path;

    public function __construct(SsmClient $ssmClient, string $path)
    {
        $this->ssmClient = $ssmClient;
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        $path = str_replace(self::ENV_PLACEHOLDER, $environment, $this->path);

        $entries = [];
        $parameters = $nextToken = null;
        while (!$parameters || $nextToken) {
            $parameters = $this->doFetch($nextToken, $path);

            foreach ($parameters['Parameters'] as $parameter) {
                $entries[] = new Entry(
                    str_replace($path, '', $parameter['Name']),
                    $parameter['Value']
                );
            }
            $nextToken = $parameters['NextToken'];
        }

        return $entries;
    }

    private function doFetch(?string $nextToken, string $path): Result
    {
        return $this->ssmClient->getParametersByPath(
            array_filter([
                'Path' => $path,
                'WithDecryption' => true,
                'NextToken' => $nextToken,
            ])
        );
    }
}
