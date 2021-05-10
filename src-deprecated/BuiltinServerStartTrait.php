<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\ResourceInterface;
use Ray\Di\InjectorInterface;
use ReflectionClass;

use function dirname;
use function str_replace;

/**
 * @deprecated User HttpResource instead
 */
trait BuiltinServerStartTrait
{
    /**
     * Hosting built server URL
     *
     * User can change it in unit test
     *
     * @var string
     */
    protected static $httpHost = 'http://127.0.0.1:8088';

    /** @var BuiltinServer */
    private static $server;

    public static function setUpBeforeClass(): void
    {
        $host = str_replace(['http://', 'https://'], '', self::$httpHost);
        $dir = dirname((new ReflectionClass(static::class))->getFileName());
        self::$server = new BuiltinServer($host, $dir . '/index.php');
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    public function getHttpResourceClient(InjectorInterface $injector, string $class): ResourceInterface
    {
        return new HttpResourceClient(self::$httpHost, $injector, $class);
    }
}
