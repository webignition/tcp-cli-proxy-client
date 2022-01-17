<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Unit\Services;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\TestCase;
use webignition\ErrorHandler\ErrorHandler;
use webignition\ObjectReflector\ObjectReflector;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class SocketFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $connectionString;
    private SocketFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $host = 'localhost';
        $port = 8000;
        $this->connectionString = (new ConnectionStringFactory())->createFromHostAndPort($host, $port);

        $this->factory = new SocketFactory($this->createErrorHandler());
    }

    public function testCreateSuccess(): void
    {
        /** @var resource $socket */
        $socket = \Mockery::mock();

        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'is_resource')
            ->with($socket)
            ->andReturnTrue()
        ;

        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'stream_socket_client')
            ->withArgs(function (string $connectionString, $errorNumber, $errorMessage) {
                $this->assertStreamSocketClientArguments($connectionString, $errorNumber, $errorMessage);

                return true;
            })
            ->andReturn($socket)
        ;

        $createdSocket = $this->factory->create($this->connectionString);
        self::assertSame($socket, $createdSocket);
    }

    public function testCreateThrowsClientCreationException(): void
    {
        $socketErrorMessage = 'socket error message';
        $socketErrorNumber = 123;

        PHPMockery::mock('webignition\TcpCliProxyClient\Services', 'stream_socket_client')
            ->withArgs(function (
                string $connectionString,
                $errorNumber,
                $errorMessage
            ) use (
                $socketErrorMessage,
                $socketErrorNumber
            ) {
                $this->assertStreamSocketClientArguments($connectionString, $errorNumber, $errorMessage);

                ObjectReflector::setProperty($this->factory, SocketFactory::class, 'errorNumber', $socketErrorNumber);
                ObjectReflector::setProperty($this->factory, SocketFactory::class, 'errorMessage', $socketErrorMessage);

                return true;
            })
            ->andReturn(false)
        ;

        try {
            $this->factory->create($this->connectionString);
            self::fail('ClientCreationException not thrown');
        } catch (ClientCreationException $clientCreationException) {
            self::assertEquals(
                new ClientCreationException(
                    $this->connectionString,
                    $socketErrorMessage,
                    $socketErrorNumber
                ),
                $clientCreationException
            );

            self::assertSame($this->connectionString, $clientCreationException->getConnectionString());
        }
    }

    public function testCreateEncapsulatesErrorExceptionInSocketErrorException(): void
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

        $factory = new SocketFactory($errorHandler);

        $this->expectExceptionObject(new SocketErrorException($errorException));

        $factory->create($this->connectionString);
    }

    private function createErrorHandler(): ErrorHandler
    {
        $errorHandler = \Mockery::mock(ErrorHandler::class);
        $errorHandler
            ->shouldReceive('start')
        ;

        $errorHandler
            ->shouldReceive('stop')
        ;

        return $errorHandler;
    }

    /**
     * @param null $errorNumber
     * @param null $errorMessage
     */
    private function assertStreamSocketClientArguments(string $connectionString, $errorNumber, $errorMessage): void
    {
        self::assertSame($this->connectionString, $connectionString);
        self::assertNull($errorNumber);
        self::assertNull($errorMessage);
    }
}
