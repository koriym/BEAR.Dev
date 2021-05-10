<?php


namespace BEAR\Dev\Fake;


use BEAR\Dev\Http\BuiltinServerStartTrait;

class FakeServer
{
    use BuiltinServerStartTrait;

    public function start(): void
    {
        $this->setUpBeforeClass();
    }

    public function stop(): void
    {
        $this->tearDownAfterClass();
    }
}