<?php

declare(strict_types=1);

namespace BEAR\Dev;

use PHPUnit\Framework\TestCase;

class DevTest extends TestCase
{
    /**
     * @var Dev
     */
    protected $dev;

    protected function setUp() : void
    {
        $this->dev = new Dev;
    }

    public function testIsInstanceOfDev() : void
    {
        $actual = $this->dev;
        $this->assertInstanceOf(Dev::class, $actual);
    }
}
