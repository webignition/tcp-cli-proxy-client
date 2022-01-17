<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Unit;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\ErrorHandler\ErrorHandler;
use webignition\ObjectReflector\ObjectReflector;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;

class ClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRequestEncapsulatesErrorExceptionInSocketErrorException(): void
    {
        $errorException = new \ErrorException('error exception message');

        $errorHandler = \Mockery::mock(ErrorHandler::class);
        $errorHandler
            ->shouldReceive('start')
        ;

        $errorHandler
            ->shouldReceive('stop')
            ->andThrow($errorException)
        ;

        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'stream_socket_client');
        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'is_resource')
            ->andReturnTrue()
        ;
        PHPMockery::mock('webignition\TcpCliProxyClient', 'fwrite');
        PHPMockery::mock('webignition\TcpCliProxyClient', 'feof')
            ->andReturnTrue()
        ;
        PHPMockery::mock('webignition\TcpCliProxyClient', 'fgets');
        PHPMockery::mock('webignition\TcpCliProxyClient', 'fclose');

        $client = new Client('connection string');
        ObjectReflector::setProperty($client, Client::class, 'errorHandler', $errorHandler);

        $this->expectExceptionObject(new SocketErrorException($errorException));

        $client->request('request content');
    }
}
