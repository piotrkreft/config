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

    protected function setUp(): void
    {
        $this->mockSsmClient = $this->getMockBuilder(SsmClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['getParametersByPath', 'getParameters'])
            ->getMock();
    }

    public function testShouldFetchByPath(): void
    {
        // given
        $adapter = new AwsSsm(
            $this->mockSsmClient,
            '/{env}/global/'
        );

        $this->mockSsmClient
            ->expects($this->exactly(2))
            ->method('getParametersByPath')
            ->withConsecutive(
                [
                    [
                        'Path' => '/dev/global/',
                        'WithDecryption' => true,
                    ],
                ],
                [
                    [
                        'Path' => '/dev/global/',
                        'WithDecryption' => true,
                        'NextToken' => 'tst',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                new Result([
                    'Parameters' => [
                        ['Name' => '/dev/global/VAR_1', 'Value' => 'value_1'],
                    ],
                    'NextToken' => 'tst',
                ]),
                new Result([
                    'Parameters' => [
                        ['Name' => '/dev/global/VAR_2', 'Value' => 'value_2'],
                    ],
                    'NextToken' => null,
                ])
            );

        // when
        $entries = $adapter->fetch('dev');

        // then
        $this->assertEquals(
            [
                new Entry('VAR_1', 'value_1'),
                new Entry('VAR_2', 'value_2'),
            ],
            $entries
        );
    }

    public function testShouldFetch(): void
    {
        // given
        $adapter = new AwsSsm(
            $this->mockSsmClient,
            null
        );

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
                    'NextToken' => null,
                ])
            );

        // when
        $entries = $adapter->fetch('dev');

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
