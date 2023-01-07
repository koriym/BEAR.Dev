<?php

declare(strict_types=1);

namespace BEAR\Dev;

use BEAR\Resource\ResourceObject;

interface TemplatePathInterface
{
    public function getTemplatePath(ResourceObject $ro): string;
}
