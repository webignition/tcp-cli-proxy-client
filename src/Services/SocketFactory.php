<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Services;

use webignition\ErrorHandler\ErrorHandler;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Exception\SocketErrorException;

class SocketFactory
{
    private ?int $errorNumber;
    private ?string $errorMessage;
    private ErrorHandler $errorHandler;

    public function __construct(ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param string $connectionString
     *
     * @return resource
     *
     * @throws ClientCreationException
     * @throws SocketErrorException
     */
    public function create(string $connectionString)
    {
        $this->errorHandler->start();
        $socket = stream_socket_client(
            $connectionString,
            $this->errorNumber,
            $this->errorMessage
        );

        try {
            $this->errorHandler->stop();
        } catch (\ErrorException $errorException) {
            throw new SocketErrorException($errorException);
        }

        if (!is_resource($socket)) {
            throw new ClientCreationException(
                $connectionString,
                (string) $this->errorMessage,
                (int) $this->errorNumber
            );
        }

        return $socket;
    }
}
