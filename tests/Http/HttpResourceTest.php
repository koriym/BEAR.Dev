<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use PHPUnit\Framework\TestCase;

use function assert;
use function dirname;
use function file_exists;

class HttpResourceTest extends TestCase
{
    /** @var HttpResource $resource */
    private $resource;

    public function setUp(): void
    {
        $index = dirname(__DIR__) . '/Fake/app/public/index.php';
        assert(file_exists($index));
        $this->resource = new HttpResource('127.0.0.1:8099', $index, __DIR__ . '/log/app.log');
    }

    public function testOnGet(): void
    {
        $ro = $this->resource->get('http://127.0.0.1:8099/');
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('"method": "onGet"', $ro->view);
    }

    public function testOnPost(): void
    {
        $ro = $this->resource->post('/');
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('"method": "onPost"', $ro->view);
    }

    public function testOnPut(): void
    {
        $ro = $this->resource->put('/');
        $this->assertSame(200, $ro->code);
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('"method": "onPut"', $ro->view);
    }

    public function testOnPatch(): void
    {
        $ro = $this->resource->patch('/');
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('"method": "onPatch"', $ro->view);
    }

    public function testOnDelete(): void
    {
        $ro = $this->resource->delete('/');
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('"method": "onDelete"', $ro->view);
    }

    public function testStdErrLog(): void
    {
        $index = dirname(__DIR__) . '/Fake/app/public/index.php';
        $resource = new HttpResource('127.0.0.1:8099', $index);
        $ro = $resource->get('http://127.0.0.1:8099/');
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('"method": "onGet"', $ro->view);
    }
}
