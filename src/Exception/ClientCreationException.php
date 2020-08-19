<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Exception;

class ClientCreationException extends \Exception
{
    private string $connectionString;

    public function __construct(string $connectionString, string $message, int $code)
    {
        parent::__construct($message, $code);

        $this->connectionString = $connectionString;
    }

    public function getConnectionString(): string
    {
        return $this->connectionString;
    }
}
