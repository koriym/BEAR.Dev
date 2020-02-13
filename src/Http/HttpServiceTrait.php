<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use GuzzleHttp\Client;

trait HttpServiceTrait
{
    /**
     * @var HttpService
     */
    private static $httpService;

    /**
     * @var Client
     */
    private static $client;

    /**
     * @var string
     */
    private $hotelId;

    public static function setUpBeforeClass() : void
    {
        self::$httpService = new HttpService;
        self::$httpService->start();
        self::$client = self::$httpService->getClient();
    }

    public static function tearDownAfterClass() : void
    {
        self::$httpService->stop();
    }
}
