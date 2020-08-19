<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use webignition\TcpCliProxyClient\Client;

class ClientTest extends TestCase
{
    private string $outPath;
    private OutputInterface $output;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outPath = __DIR__ . '/out';
        $outFile = fopen($this->outPath, 'w+');

        if (is_resource($outFile)) {
            $this->output = new StreamOutput($outFile);
        } else {
            $this->fail('Failed to create output file');
        }

        self::assertStringEqualsFile($this->outPath, '');

        $this->client = Client::createFromHostAndPort('localhost', 8000);
        $this->client = $this->client->withOutput($this->output);
    }

    public function testRequest()
    {
        $this->client->request('ls ' . __FILE__);

        self::assertStringEqualsFile($this->outPath, __FILE__ . "\n\n0");
    }

    public function testResponseIsWrittenAsReceived()
    {
        $fixturePath = __DIR__ . '/fixture.sh';
        $now = microtime(true);
        $writeIntervals = [];

        $this->client->request($fixturePath, function (string $buffer) use (&$writeIntervals, &$now) {
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
