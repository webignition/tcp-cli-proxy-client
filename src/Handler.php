<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

use webignition\TcpCliProxyClient\Exception\TimeoutException;

class Handler
{
    /**
     * @var resource
     */
    private $socket;

    /**
     * @var callable[]
     */
    private array $callbacks = [];

    /**
     * @throws TimeoutException
     */
    public function handle(string $request): void
    {
        while (!feof($this->socket)) {
            $streamMetadata = stream_get_meta_data($this->socket);
            if (true === $streamMetadata['timed_out']) {
                throw new TimeoutException();
            }

            $buffer = (string) fgets($this->socket);

            foreach ($this->callbacks as $callback) {
                $callbackReturn = $callback($buffer, $request);

                if (null !== $callbackReturn) {
                    $buffer = $callbackReturn;
                }
            }
        }
    }

    public function addCallback(callable $callback): self
    {
        $this->callbacks[] = $callback;

        return $this;
    }

    /**
     * @param resource $socket
     *
     * @return $this
     */
    public function withSocket($socket): self
    {
        $this->socket = $socket;

        return $this;
    }
}
