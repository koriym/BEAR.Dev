<?php

declare(strict_types=1);

namespace MyVendor\MyProject;

use BEAR\AppMeta\Meta;
use BEAR\Package\Injector as PackageInjector;
use Ray\Di\InjectorInterface;
use function dirname;
use function str_replace;
use function var_dump;

final class Injector
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function getInstance(string $context): InjectorInterface
    {

        $meta = new Meta(__NAMESPACE__, $context, dirname(__DIR__));
        $cacheNamespace = str_replace('/', '_', dirname(__DIR__)) . $context;
        var_dump($cacheNamespace);

        return PackageInjector::getInstance(__NAMESPACE__, $context, dirname(__DIR__));
    }
}
