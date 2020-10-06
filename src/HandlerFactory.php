<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient;

class HandlerFactory
{
    public function createWithScalarOutput(string &$output, ?int &$exitCode = null): Handler
    {
        $handler = new Handler();

        $handler->addCallback(function (string $buffer) use (&$output) {
            if (false === ctype_digit($buffer)) {
                $output .= $buffer;
            }
        });

        $handler->addCallback(function (string $buffer) use (&$exitCode) {
            if (ctype_digit($buffer)) {
                $exitCode = (int) $buffer;
            }
        });

        return $handler;
    }
}
