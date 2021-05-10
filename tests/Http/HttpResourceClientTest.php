<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\ResourceInterface;
use InvalidArgumentException;
use MyVendor\MyProject\Injector;
use PHPUnit\Framework\TestCase;

use function dirname;

class HttpResourceClientTest extends TestCase
{
    use BuiltinServerStartTrait;

    /** @var ResourceInterface */
    private $resource;

    public function setUp(): void
    {
        require_once dirname(__DIR__) . '/Fake/app/vendor/autoload.php';

        parent::setUp();
        $this->resource = $this->getHttpResourceClient(Injector::getInstance('hal-app'), self::class);
    }

    public function testAssertHttpResourceClient(): void
    {
        $this->assertInstanceOf(HttpResourceClient::class, $this->resource);
    }

    public function testGet(): void
    {
        $ro = $this->resource->get('http://127.0.0.1:8088/');
        $this->assertSame(200, $ro->code);
    }

    public function testAGet(): void
    {
        $ro = $this->resource->get('/');
        $this->assertSame(200, $ro->code);
    }

    public function testPost(): void
    {
        $ro = $this->resource->post('http://127.0.0.1:8088/');
        $this->assertSame(405, $ro->code);
    }

    public function testPut(): void
    {
        $ro = $this->resource->put('http://127.0.0.1:8088/');
        $this->assertSame(405, $ro->code);
    }

    public function testPatch(): void
    {
        $ro = $this->resource->patch('http://127.0.0.1:8088/');
        $this->assertSame(405, $ro->code);
    }

    public function testDelete(): void
    {
        $ro = $this->resource->delete('http://127.0.0.1:8088/');
        $this->assertSame(405, $ro->code);
    }

    public function testInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HttpResourceClient('__invalid__', Injector::getInstance('app'), self::class);
    }
}
