<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Services;

class ConnectionStringFactory
{
    public function createFromHostAndPort(string $host, int $port): string
    {
        return sprintf('tcp://%s:%d', $host, $port);
    }
}
