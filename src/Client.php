<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use webignition\TcpCliProxyClient\Exception\ClientCreationException;
use webignition\TcpCliProxyClient\Services\ErrorHandler;
use webignition\TcpCliProxyClient\Services\SocketFactory;

class Client
{
    private string $connectionString;

    private ErrorHandler $errorHandler;
    private SocketFactory $socketFactory;
    private OutputInterface $output;

    public function __construct(string $connectionString)
    {
        $this->connectionString = $connectionString;

        $this->errorHandler = new ErrorHandler();
        $this->socketFactory = new SocketFactory($this->errorHandler);
        $this->output = new StreamOutput(STDOUT);
    }

    public static function createFromHostAndPort(string $host, int $port): self
    {
        return new Client(
            (string) sprintf('tcp://%s:%d', $host, $port)
        );
    }

    public function withOutput(OutputInterface $output): self
    {
        $new = clone $this;
        $new->output = $output;

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
        $socket = $this->socketFactory->create($this->connectionString);

        $filter = $filter ?? function (string $buffer) {
            return $buffer;
        };

        $this->errorHandler->start();
        fwrite($socket, $request . "\n");
        while (!feof($socket)) {
            $buffer = (string) fgets($socket);

            (function (string $buffer, callable $foo) {
                $buffer = $foo($buffer);
                $this->output->write($buffer);
            })($buffer, $filter);
        }
        fclose($socket);
        $this->errorHandler->stop();
    }
}
