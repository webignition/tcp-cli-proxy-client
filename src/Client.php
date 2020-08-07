<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use Socket\Raw\Factory;
use webignition\TcpCliProxyModels\Output;

class Client
{
    private const READ_LENGTH = 2048;

    private string $host;
    private int $port;
    private ConnectionFactory $connectionFactory;
    private int $readLength;

    public function __construct(
        string $host,
        int $port,
        ?ConnectionFactory $connectionFactory = null,
        int $readLength = self::READ_LENGTH
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->connectionFactory = $connectionFactory ?? new ConnectionFactory(new Factory());
        $this->readLength = $readLength;
    }

    public function request(string $request): Output
    {
        $socket = $this->connectionFactory->create($this->host, $this->port);
        $socket->send($request, MSG_EOF);

        $response = '';
        while ('' !== ($partialResponse = $socket->read($this->readLength))) {
            $response .= $partialResponse;
        }

        return Output::fromString($response);
    }
}
