<?php

declare(strict_types=1);

namespace MyVendor\MyProject\Inetgration;

use BEAR\Dev\Http\HttpResourceClient;
use BEAR\Dev\Http\BuiltinServerStartTrait;
use MyVendor\MyProject\Workflow\DemoTest as Workflow;

class DemoTest extends Workflow
{
    use BuiltinServerStartTrait;

    protected function setUp() : void
    {
        parent::setUp();
        $token = '_secret_token_';
        $_SERVER['Authorization'] = $token;
        $logFile = sprintf('%s/http_log/%s.log', __DIR__, __CLASS__);
        $this->resource = new HttpResourceClient($this->injector, $logFile);
    }

    public function testIndex()
    {
        $index = $this->resource->get('http://127.0.0.1:8088/');
        $this->assertSame(200, $index->code);

        return $index;
    }
}
