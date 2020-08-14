<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Services\ErrorHandler;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class Client
{
    private string $host;
    private int $port;

    private ErrorHandler $errorHandler;
    private SocketFactory $socketFactory;

    /**
     * @var resource
     */
    private $out;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;

        $this->errorHandler = new ErrorHandler();
        $this->socketFactory = new SocketFactory($this->errorHandler);
        $this->out = STDOUT;
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

    /**
     * @param string $request
     * @param callable|null $filter
     *
     * @throws \ErrorException
     * @throws ClientCreationException
     */
    public function request(string $request, ?callable $filter = null): void
    {
        $socket = $this->createSocket($this->host, $this->port);

        $filter = $filter ?? function (string $buffer) {
            return $buffer;
        };

        $this->errorHandler->start();
        fwrite($socket, $request . "\n");
        while (!feof($socket)) {
            $buffer = (string) fgets($socket);

            (function (string $buffer, callable $foo) {
                $buffer = $foo($buffer);
                fwrite($this->out, $buffer);
            })($buffer, $filter);
        }
        fclose($socket);
        $this->errorHandler->stop();
    }

    /**
     * @param string $host
     * @param int $port
     *
     * @return resource
     *
     * @throws ClientCreationException
     * @throws \ErrorException
     */
    private function createSocket(string $host, int $port)
    {
        return $this->socketFactory->create($host, $port);
    }
}
