<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Integration;

use PHPUnit\Framework\TestCase;
use webignition\TcpCliProxyClient\StreamingClient;

class StreamingClientTest extends TestCase
{
    private string $outPath;

    /**
     * @var resource
     */
    private $out;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outPath = __DIR__ . '/out';
        $out = fopen($this->outPath, 'w+');

        if (is_resource($out)) {
            $this->out = $out;
        } else {
            $this->fail('Failed to create output file');
        }

        self::assertStringEqualsFile($this->outPath, '');
    }

    public function testRequest()
    {
        $client = new StreamingClient('localhost', 8000, $this->out);
        $client->request('ls ' . __FILE__);

        self::assertStringEqualsFile($this->outPath, __FILE__ . "\n\n0");
    }

    public function testResponseIsWrittenAsReceived()
    {
        $client = new StreamingClient('localhost', 8000, $this->out);

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        self::assertFileExists($this->outPath);
        unlink($this->outPath);
    }
}
