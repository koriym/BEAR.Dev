<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use GuzzleHttp\Client;

trait BuiltinServerStartTrait
{
    /**
     * @var BuiltinServer
     */
    private static $server;

    /**
     * @var Client
     */
    private static $client;

    public static function setUpBeforeClass() : void
    {
        self::$server = new BuiltinServer;
        self::$server->start();
        self::$client = self::$server->getClient();
    }

    public static function tearDownAfterClass() : void
    {
        self::$server->stop();
    }
}
