<?php

declare(strict_types=1);

namespace BEAR\Dev\Halo;

use BEAR\Resource\ResourceInterface;
use MyVendor\MyProject\Injector;
use PHPUnit\Framework\TestCase;

class HaloModuleTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getInstance('dev-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testModule(): void
    {
        $ro = $this->resource->get('page://self/');
        $view = (string) $ro;
        $this->assertStringContainsString('<!-- resource:page://self/index -->', $view);
        $this->assertStringContainsString('<!-- resource_tab_end -->', $view);
    }
}
