<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

class StreamingClient
{
    private string $host;
    private int $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var resource
     */
    private $out;

    /**
     * @param string $host
     * @param int $port
     * @param resource $out
     */
    public function __construct(string $host, int $port, $out)
    {
        $this->host = $host;
        $this->port = $port;
        $this->out = $out;

        $socket = stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 30);

        if (false === $socket) {
            throw new \RuntimeException('client connection failed');
        }

        $this->socket = $socket;
    }

    public function request(string $request, ?callable $filter = null): void
    {
        $filter = $filter ?? function (string $buffer) {
            return $buffer;
        };

        fwrite($this->socket, $request . "\n");
        while (!feof($this->socket)) {
            $buffer = (string) fgets($this->socket);

            (function (string $buffer, callable $foo) {
                $buffer = $foo($buffer);
                fwrite($this->out, $buffer);
            })($buffer, $filter);
        }
        fclose($this->socket);
    }
}
