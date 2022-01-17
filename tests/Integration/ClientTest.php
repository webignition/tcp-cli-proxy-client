<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyClient\Handler;
use webignition\TcpCliProxyClient\HandlerFactory;

class ClientTest extends TestCase
{
    private Client $client;
    private HandlerFactory $handlerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::createFromHostAndPort('localhost', 8000);
        $this->handlerFactory = new HandlerFactory();
    }

    public function testRequest(): void
    {
        $output = '';
        $exitCode = null;

        $handler = $this->handlerFactory->createWithScalarOutput($output, $exitCode);

        $this->client->request('ls ' . __FILE__, $handler);

        self::assertSame(0, $exitCode);
        self::assertSame(__FILE__ . "\n\n", $output);
    }

    public function testResponseIsWrittenAsReceived(): void
    {
        $fixturePath = __DIR__ . '/fixture.sh';
        $now = microtime(true);
        $writeIntervals = [];

        $handler = (new Handler())
            ->addCallback(function () use (&$writeIntervals, &$now) {
                $writeIntervals[] = microtime(true) - $now;
                $now = microtime(true);
            })
        ;

        $this->client->request($fixturePath);
        $this->client->request($fixturePath, $handler);

        $echoWriteIntervals = array_slice($writeIntervals, 0, 2);

        foreach ($echoWriteIntervals as $interval) {
            self::assertGreaterThanOrEqual(0.1, $interval);
            self::assertLessThan(0.4, $interval);
        }
    }

    public function testRequestIsAvailableToHandlerCallback(): void
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
