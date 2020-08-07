<?php

declare(strict_types=1);

namespace webignition\TcpCliProxyClient\Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use webignition\TcpCliProxyClient\Client;
use webignition\TcpCliProxyModels\Output;

class ClientTest extends TestCase
{
    public function testRequest()
    {
        $client = new Client('localhost', 8000);
        $output = $client->request('ls ' . __FILE__);

        self::assertEquals(
            new Output(0, __FILE__),
            $output
        );
    }
}
