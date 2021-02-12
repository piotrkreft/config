<?php

declare(strict_types=1);

namespace PK\Tests\Config\StorageAdapter;

use Aws\Result;
use Aws\Ssm\SsmClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PK\Config\Entry;
use PK\Config\StorageAdapter\AwsSsm;

class AwsSsmTest extends TestCase
{
    /**
     * @var SsmClient|MockObject
     */
    private $mockSsmClient;

    /**
     * @var AwsSsm
     */
    private $adapter;

    protected function setUp(): void
    {
        $this->mockSsmClient = $this->getMockBuilder(SsmClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['getParametersByPath', 'getParameters'])
            ->getMock();

        $this->adapter = new AwsSsm(
            $this->mockSsmClient
        );
    }

    public function testShouldFetch(): void
    {
        // given
        $this->mockSsmClient
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->withConsecutive(
                [
                    [
                        'WithDecryption' => true,
                    ],
                ],
                [
                    [
                        'WithDecryption' => true,
                        'NextToken' => 'tst',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Result([
                    'Parameters' => [
                        ['Name' => '/path/VAR_1', 'Value' => 'value_1'],
                    ],
                    'NextToken' => 'tst',
                ]),
                new Result([
                    'Parameters' => [
                        ['Name' => 'VAR_2', 'Value' => 'value_2'],
                    ],
                ])
            );

        // when
        $entries = $this->adapter->fetch('dev');

        // then
        $this->assertEquals(
            [
                new Entry('/path/VAR_1', 'value_1'),
                new Entry('VAR_2', 'value_2'),
            ],
            $entries
        );
    }
}
