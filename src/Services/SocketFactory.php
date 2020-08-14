<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Services;

use webignition\TcpCliProxyClient\Exception\ClientCreationException;

class SocketFactory
{
    private ?int $errorNumber;
    private ?string $errorMessage;

    /**
     * @param string $host
     * @param int $port
     *
     * @return resource
     *
     * @throws ClientCreationException
     */
    public function create(string $host, int $port)
    {
        $socket = stream_socket_client(
            sprintf('tcp://%s:%d', $host, $port),
            $this->errorNumber,
            $this->errorMessage
        );

        if (!is_resource($socket)) {
            throw new ClientCreationException((string) $this->errorMessage, (int) $this->errorNumber);
        }

        return $socket;
    }
}
