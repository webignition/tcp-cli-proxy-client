<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Services\ErrorHandler;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class Client
{
    /**
     * @var resource
     */
    private $socket;

    /**
     * @var resource
     */
    private $out;

    private ErrorHandler $errorHandler;

    /**
     * @param string $host
     * @param int $port
     * @param SocketFactory $socketFactory
     * @param ErrorHandler $errorHandler
     * @param resource $out
     *
     * @throws ClientCreationException
     * @throws \ErrorException
     */
    public function __construct(
        string $host,
        int $port,
        SocketFactory $socketFactory,
        ErrorHandler $errorHandler,
        $out
    ) {
        $this->errorHandler = $errorHandler;
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
     * @throws \ErrorException
     */
    public static function createClient(string $host, int $port): self
    {
        $errorHandler = new ErrorHandler();

        return new Client(
            $host,
            $port,
            new SocketFactory($errorHandler),
            $errorHandler,
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

    /**
     * @param string $request
     * @param callable|null $filter
     *
     * @throws \ErrorException
     */
    public function request(string $request, ?callable $filter = null): void
    {
        $filter = $filter ?? function (string $buffer) {
            return $buffer;
        };

        $this->errorHandler->start();
        fwrite($this->socket, $request . "\n");
        while (!feof($this->socket)) {
            $buffer = (string) fgets($this->socket);

            (function (string $buffer, callable $foo) {
                $buffer = $foo($buffer);
                fwrite($this->out, $buffer);
            })($buffer, $filter);
        }
        fclose($this->socket);
        $this->errorHandler->stop();
    }
}
