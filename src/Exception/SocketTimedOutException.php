<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Exception;

class SocketTimedOutException extends \Exception
{
    public function __construct(
        public readonly int $timeoutInSeconds
    ) {
        parent::__construct(sprintf('Socket timed out after %d seconds', $timeoutInSeconds));
    }
}
