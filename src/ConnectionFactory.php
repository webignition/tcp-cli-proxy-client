<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use Socket\Raw\Factory;
use Socket\Raw\Socket;

class ConnectionFactory
{
    private Factory $socketFactory;

    public function __construct(Factory $socketFactory)
    {
        $this->socketFactory = $socketFactory;
    }

    public function create(string $host, int $port): Socket
    {
        return $this->socketFactory->createClient(sprintf(
            'tcp://%s:%d',
            $host,
            $port
        ));
    }
}
