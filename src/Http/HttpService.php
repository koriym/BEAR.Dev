<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use GuzzleHttp\Client;
use function sprintf;
use Symfony\Component\Process\Process;

final class HttpService
{
    /**
     * @var string
     */
    private $baseHost = '127.0.0.1:8088';

    /**
     * @var Process
     */
    private $process;

    public function start() : void
    {
        $command = sprintf('php -S %s -t %s', $this->baseHost, dirname(__DIR__, 3) . '/public');
        $process = new Process($command);
        $process->disableOutput();
        $process->start();
        usleep(1000000); //wait for server to get going
        $this->process = $process;
    }

    public function stop() : void
    {
        $this->process->stop();
    }

    public function getClient() : Client
    {
        return new Client(['base_uri' => sprintf('http://%s', $this->baseHost)]);
    }
}
