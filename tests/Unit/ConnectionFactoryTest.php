<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Unit\Services;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Socket\Raw\Factory;
use Socket\Raw\Socket;
use webignition\TcpCliProxyClient\ConnectionFactory;

class ConnectionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreate()
    {
        $host = 'hostname';
        $port = 1234;

        $socket = Mockery::mock(Socket::class);

        $socketFactory = Mockery::mock(Factory::class);
        $socketFactory
            ->shouldReceive('createClient')
            ->with('tcp://' . $host . ':' . (string) $port)
            ->andReturn($socket);

        $connectionFactory = new ConnectionFactory($socketFactory);

        self::assertSame($socket, $connectionFactory->create($host, $port));
    }
}
