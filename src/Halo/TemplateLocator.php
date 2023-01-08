<?php

declare(strict_types=1);

namespace BEAR\Dev\Halo;

use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Resource\ResourceObject;
use Ray\Aop\ReflectionClass;
use Ray\Aop\WeavedInterface;
use Ray\Di\Di\Named;

use function get_class;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;

final class TemplateLocator
{
    public function __construct(
        private AbstractAppMeta $meta,
        #[Named('qiq_paths')] private array $qiqPaths = [],
        #[Named('qiq_extension')] private string $qiqExt = '',
    ) {
    }

    public function get(ResourceObject $ro): string
    {
        return $this->qiqExt ? $this->getQiq($ro) : '';
    }

    public function getQiq(ResourceObject $ro): string
    {
        foreach ($this->qiqPaths as $path) {
            $len = strlen(sprintf('%s\Resource', $this->meta->name));
            $roPath = str_replace('\\', '/', substr($this->getClass($ro), $len + 1));

            return sprintf('%s/%s%s', $path, $roPath, $this->qiqExt);
        }

        return '';
    }

    private function getClass(ResourceObject $ro): string
    {
        if ($ro instanceof WeavedInterface) {
            /** @var \ReflectionClass<ResourceObject> $parentClass */
            $parentClass = (new ReflectionClass($ro))->getParentClass();

            return $parentClass->name;
        }

        return get_class($ro);
    }
}
