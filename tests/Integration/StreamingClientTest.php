<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\TcpCliProxyClient\StreamingClient;

class StreamingClientTest extends TestCase
{
    public function testRequest()
    {
        $outPath = __DIR__ . '/out';

        $out = fopen($outPath, 'w+');
        self::assertStringEqualsFile($outPath, '');

        $client = new StreamingClient('localhost', 8000, $out);
        $client->request('ls ' . __FILE__);

        self::assertStringEqualsFile($outPath, __FILE__ . "\n\n0");

        unlink($outPath);
    }

    public function testResponseIsWrittenAsReceived()
    {
        $outPath = __DIR__ . '/out';

        $out = fopen($outPath, 'w+');
        self::assertStringEqualsFile($outPath, '');

        $client = new StreamingClient('localhost', 8000, $out);

        $fixturePath = __DIR__ . '/fixture.sh';
        $now = microtime(true);
        $writeIntervals = [];

        $client->request($fixturePath, function (string $buffer) use (&$writeIntervals, &$now) {
            $writeIntervals[] = microtime(true) - $now;
            $now = microtime(true);

            return $buffer;
        });

        $echoWriteIntervals = array_slice($writeIntervals, 0, 2);

        foreach ($echoWriteIntervals as $interval) {
            self::assertGreaterThanOrEqual(0.1, $interval);
            self::assertLessThan(0.11, $interval);
        }

        unlink($outPath);
    }
}
