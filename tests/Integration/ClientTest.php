<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::createFromHostAndPort('localhost', 8000);
    }

    public function testRequest()
    {
        $output = '';

        $handler = new Handler();
        $handler = $handler->addCallback(function (string $buffer) use (&$output) {
            $output .= $buffer;
        });

        $this->client->request('ls ' . __FILE__, $handler);

        self::assertSame(__FILE__ . "\n\n0", $output);
    }

    public function testResponseIsWrittenAsReceived()
    {
        $fixturePath = __DIR__ . '/fixture.sh';
        $now = microtime(true);
        $writeIntervals = [];

        $handler = (new Handler())
            ->addCallback(function () use (&$writeIntervals, &$now) {
                $writeIntervals[] = microtime(true) - $now;
                $now = microtime(true);
            });

        $this->client->request($fixturePath);
        $this->client->request($fixturePath, $handler);

        $echoWriteIntervals = array_slice($writeIntervals, 0, 2);

        foreach ($echoWriteIntervals as $interval) {
            self::assertGreaterThanOrEqual(0.1, $interval);
            self::assertLessThan(0.4, $interval);
        }
    }

    public function testRequestIsAvailableToHandlerCallback()
    {
        $passedRequest = null;

        $handler = new Handler();
        $handler = $handler->addCallback(function (string $buffer, string $request) use (&$passedRequest) {
            if (null === $passedRequest) {
                $passedRequest = $request;
            }

            return $buffer;
        });

        $request = 'ls ' . __FILE__;

        $this->client->request($request, $handler);

        self::assertSame($request, $passedRequest);
    }
}
