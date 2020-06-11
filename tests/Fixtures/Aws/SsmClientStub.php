<?php

declare(strict_types=1);

namespace PK\Tests\Config\Fixtures\Aws;

use Aws\Result;
use Aws\Ssm\SsmClient;

class SsmClientStub extends SsmClient
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $args)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, array $args): Result
    {
        return new Result([
            'Parameters' => [
                ['Name' => 'some_var_dev', 'Value' => 'value_2_ssm'],
            ],
            'NextToken' => null,
        ]);
    }
}
