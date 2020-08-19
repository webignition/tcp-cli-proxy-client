<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Exception;

class SocketErrorException extends \Exception
{
    private \ErrorException $errorException;

    public function __construct(\ErrorException $errorException)
    {
        parent::__construct($errorException->getMessage(), $errorException->getCode(), $errorException);

        $this->errorException = $errorException;
    }

    public function getErrorException(): \ErrorException
    {
        return $this->errorException;
    }
}
