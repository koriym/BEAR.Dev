<?php

declare(strict_types=1);

namespace BEAR\Dev\Halo;

use BEAR\Dev\FakeHalo;
use BEAR\Dev\TemplatePathInterface;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use PHPUnit\Framework\TestCase;

class HaloRendererTest extends TestCase
{
    public function testRender(): void
    {
        $originalRenderer = new class implements RenderInterface, TemplatePathInterface
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

            public function getTemplatePath(ResourceObject $ro): string
            {
                return 'template_path';
            }
        };
        $renderer = new HaloRenderer($originalRenderer);
        $ro = new FakeHalo();
        $view = $renderer->render($ro);
        $this->assertStringStartsWith('<html>', $view);
        $this->assertStringContainsString('<body><!-- resource:', $view);
    }
}
