<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class StreamingClient
{
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
     * @param SocketFactory $socketFactory
     * @param resource $out
     *
     * @throws ClientCreationException
     */
    public function __construct(string $host, int $port, SocketFactory $socketFactory, $out)
    {
        $this->out = $out;
        $this->socket = $socketFactory->create($host, $port);
    }

    /**
     * @param string $host
     * @param int $port
     *
     * @return self
     *
     * @throws ClientCreationException
     */
    public static function createClient(string $host, int $port): self
    {
        return new StreamingClient(
            $host,
            $port,
            new SocketFactory(),
            STDOUT
        );
    }

    /**
     * @param resource $out
     *
     * @return self
     */
    public function withOut($out): self
    {
        $new = clone $this;
        $new->out = $out;

        return $new;
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
