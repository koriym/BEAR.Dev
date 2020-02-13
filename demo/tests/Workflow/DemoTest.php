<?php

declare(strict_types=1);

namespace MyVendor\MyProject\Workflow;

use BEAR\Dev\Db\DbProfile;
use BEAR\Package\AppInjector;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;
use Ray\Di\InjectorInterface;

class DemoTest extends TestCase
{
    /**
     * @var ResourceInterface
     */
    protected $resource;

    /**
     * @var InjectorInterface
     */
    protected $injector;

    /**
     * @var DbProfile
     */
    private $dbProfile;

    protected function setUp() : void
    {
        $this->injector = (new AppInjector('Vendor\Package', 'test-app'));
        $this->dbProfile = new DbProfile($this->injector);
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
    }

    protected function tearDown() : void
    {
        $this->dbProfile->log();
    }

    public function testIndex()
    {
        $index = $this->resource->get('/index');
        $this->assertSame(200, $index->code);

        return $index;
    }

    /**
     * @depends testIndex
     */
    public function testRelx(ResourceObject $response)
    {
        $json = (string) $response;
        $href = json_decode($json)->_links->{'relx'}->href;
        $ro = $this->resource->get($href);
        $this->assertSame(200, $ro->code);

        return $ro;
    }
}
