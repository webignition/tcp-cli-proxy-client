<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use webignition\ErrorHandler\ErrorHandler;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;
use webignition\TcpCliProxyClient\Exception\SocketTimedOutException;
use webignition\TcpCliProxyClient\Exception\TimeoutException;
use webignition\TcpCliProxyClient\Services\ConnectionStringFactory;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class Client
{
    private string $connectionString;

    private ErrorHandler $errorHandler;
    private SocketFactory $socketFactory;

    public function __construct(string $connectionString)
    {
        $this->connectionString = $connectionString;

        $this->errorHandler = new ErrorHandler();
        $this->socketFactory = new SocketFactory($this->errorHandler);
    }

    public static function createFromHostAndPort(string $host, int $port): self
    {
        return new Client(
            (new ConnectionStringFactory())->createFromHostAndPort($host, $port)
        );
    }

    /**
     * @throws ClientCreationException
     * @throws SocketErrorException
     * @throws SocketTimedOutException
     * @throws \ErrorException
     */
    public function request(string $request, Handler $handler, ?int $timeoutInSeconds = null): void
    {
        $socket = $this->socketFactory->create($this->connectionString);
        if (is_int($timeoutInSeconds)) {
            stream_set_timeout($socket, $timeoutInSeconds);
        }

        $handler = $handler->withSocket($socket);

        $this->errorHandler->start();
        fwrite($socket, $request . "\n");

        try {
            $handler->handle($request);
        } catch (TimeoutException) {
            $this->errorHandler->stop();

            throw new SocketTimedOutException((int) $timeoutInSeconds);
        }

        fclose($socket);

        try {
            $this->errorHandler->stop();
        } catch (\ErrorException $errorException) {
            throw new SocketErrorException($errorException);
        }
    }
}
