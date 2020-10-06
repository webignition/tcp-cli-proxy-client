<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

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

    public function handle(string $request): void
    {
        while (!feof($this->socket)) {
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
