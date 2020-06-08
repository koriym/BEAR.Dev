<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use function dirname;
use GuzzleHttp\Client;
use RuntimeException;
use function sprintf;
use function strpos;
use Symfony\Component\Process\Process;

final class HttpService
{
    /**
     * @var string
     */
    private const HOST = '127.0.0.1:8088';

    /**
     * @var Process
     */
    private $process;

    public function start() : void
    {
        $process = new Process([
            PHP_BINARY,
            '-S',
            self::HOST,
            '-t',
            sprintf('%s/public', dirname(__DIR__, 5))
        ]);
        $process->start();
        $process->waitUntil(function ($type, $output) {
            unset($type);

            return (bool) strpos($output, self::HOST);
        });
        $this->process = $process;
    }

    public function stop() : void
    {
        $exitCode = $this->process->stop();
        if ($exitCode !== 143) {
            throw new RuntimeException((string) $exitCode);
        }
    }

    public function getClient() : Client
    {
        return new Client(['base_uri' => sprintf('http://%s', self::HOST)]);
    }
}
