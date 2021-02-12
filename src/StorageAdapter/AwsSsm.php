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

    public function __construct(SsmClient $ssmClient)
    {
        $this->ssmClient = $ssmClient;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $environment): array
    {
        $entries = [];
        $parameters = $nextToken = null;
        while (!$parameters || $nextToken) {
            $parameters = $this->doFetch($nextToken);

            foreach ($parameters['Parameters'] as $parameter) {
                $entries[] = new Entry($parameter['Name'], $parameter['Value']);
            }
            $nextToken = $parameters['NextToken'];
        }

        return $entries;
    }

    private function doFetch(?string $nextToken): Result
    {
        return $this->ssmClient->getParameters(
            array_filter([
                'WithDecryption' => true,
                'NextToken' => $nextToken,
            ])
        );
    }
}
