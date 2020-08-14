<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class SocketFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreate()
    {
        $host = 'localhost';
        $port = 8000;

        /** @var resource $socket */
        $socket = \Mockery::mock();

        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'is_resource')
            ->with($socket)
            ->andReturnTrue();

        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'stream_socket_client')
            ->withArgs(function (string $connectionString, $errorNumber, $errorMessage) use ($host, $port) {
                self::assertSame('tcp://' . $host . ':' . $port, $connectionString);
                self::assertNull($errorNumber);
                self::assertNull($errorMessage);

                return true;
            })
            ->andReturn($socket);

        $factory = new SocketFactory();

        $createdSocket = $factory->create($host, $port);
        self::assertSame($socket, $createdSocket);
    }
}
