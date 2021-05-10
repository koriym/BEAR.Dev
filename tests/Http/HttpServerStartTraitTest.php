<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Dev\Fake\FakeServer;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class HttpServerStartTraitTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function testStartStop(): void
    {
        $server = new FakeServer();
        $server->start();
        $res = file_get_contents('http://127.0.0.1:8088/');
        $this->assertStringContainsString('ok', $res);
        $server->stop();
        $this->expectWarning();
        file_get_contents('http://127.0.0.1:8088/');
    }
}
