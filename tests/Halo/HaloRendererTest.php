<?php

declare(strict_types=1);

namespace BEAR\Dev\Halo;

use BEAR\AppMeta\Meta;
use BEAR\Dev\FakeHalo;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;

class HaloRendererTest extends TestCase
{
    public function testRender(): void
    {
        $originalRenderer = new class implements RenderInterface
        {
            public function render(ResourceObject $ro)
            {
                return '<html>
    <head>
        <title>Greting</title>
    </head>
    <body><h1>Greeting</h1><p>Hello World!</p></body>
</html>';
            }
        };
        $renderer = new HaloRenderer($originalRenderer, new TemplateLocator(new Meta('MyVendor\MyProject')));
        $ro = new FakeHalo();
        $view = $renderer->render($ro);
        $this->assertStringStartsWith('<html>', $view);
        $this->assertStringContainsString('<body><!-- resource:', $view);
    }
}
