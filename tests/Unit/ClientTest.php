<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Unit\Services;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Socket\Raw\Socket;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\ConnectionFactory;
use webignition\TcpCliProxyModels\Output;

class ClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider requestDataProvider
     */
    public function testRequest(Client $client, string $request, Output $expectedOutput)
    {
        $output = $client->request($request);

        self::assertEquals($expectedOutput, $output);
    }

    public function requestDataProvider(): array
    {
        return [
            'response smaller than read length' => [
                'client' => new Client(
                    'localhost',
                    8000,
                    $this->createConnectionFactory(
                        'localhost',
                        8000,
                        $this->createSocket(
                            './command',
                            $this->createRawResponse([
                                '0',
                                'resp'
                            ]),
                            8
                        )
                    ),
                    8
                ),
                'request' => './command',
                'expectedOutput' => new Output(0, 'resp'),
            ],
            'response larger than read length' => [
                'client' => new Client(
                    'localhost',
                    8000,
                    $this->createConnectionFactory(
                        'localhost',
                        8000,
                        $this->createSocket(
                            './command',
                            $this->createRawResponse([
                                '0',
                                'response line 1',
                                'response line 2',
                            ]),
                            8
                        )
                    ),
                    8
                ),
                'request' => './command',
                'expectedOutput' => new Output(
                    0,
                    'response line 1' . "\n" .
                    'response line 2'
                ),
            ],
        ];
    }


    private function createConnectionFactory(string $host, int $port, Socket $socket): ConnectionFactory
    {
        $connectionFactory = Mockery::mock(ConnectionFactory::class);
        $connectionFactory
            ->shouldReceive('create')
            ->with($host, $port)
            ->andReturn($socket);

        return $connectionFactory;
    }

    private function createSocket(string $request, string $rawResponse, int $readLength): Socket
    {
        $socket = Mockery::mock(Socket::class);
        $socket
            ->shouldReceive('send')
            ->with($request, MSG_EOF)
            ->andReturn(strlen($request));

        $responseChunks = (array) str_split($rawResponse, $readLength);

        $readCount = 0;
        $readLimit = count($responseChunks);
        $socket
            ->shouldReceive('read')
            ->with($readLength)
            ->andReturnUsing(function () use ($responseChunks, &$readCount, $readLimit) {
                if ($readCount === $readLimit) {
                    return '';
                }

                $partialResponse = $responseChunks[$readCount];

                $readCount++;

                return $partialResponse;
            });

        return $socket;
    }

    /**
     * @param string[] $lines
     *
     * @return string
     */
    private function createRawResponse(array $lines): string
    {
        return implode("\n", $lines);
    }
}
