<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Dev\QueryMerger;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri as ResourceUri;
use Koriym\PhpServer\PhpServer;
use LogicException;
use RuntimeException;

use function escapeshellarg;
use function exec;
use function fclose;
use function feof;
use function fgets;
use function file_exists;
use function file_put_contents;
use function http_build_query;
use function implode;
use function in_array;
use function is_resource;
use function json_encode;
use function proc_close;
use function proc_open;
use function sprintf;
use function trim;

use const FILE_APPEND;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

final class HttpResource implements ResourceInterface
{
    /** @var string */
    private $logFile;

    /** @var string */
    private $baseUri;

    /** @var PhpServer */
    private static $server;

    /** @var QueryMerger */
    private $queryMerger;

    /** @var CreateResponse */
    private $createResponse;

    public function __construct(string $host, string $index, string $logFile = 'php://stderr')
    {
        $this->baseUri = sprintf('http://%s', $host);
        $this->logFile = $logFile;
        $this->resetLog($logFile);
        $this->startServer($host, $index);
        $this->queryMerger = new QueryMerger();
        $this->createResponse = new CreateResponse();
    }

    private function startServer(string $host, string $index): void
    {
        /** @var array<string> $started */
        static $started = [];
        $id = $host . $index;
        if (in_array($id, $started, true)) {
            return;
        }

        self::$server = new PhpServer($host, $index);
        self::$server->start();
        $started[] = $id;
    }

    private function resetLog(string $logFile): void
    {
        /** @var array<string> $started */
        static $started = [];
        if (in_array($logFile, $started, true) || ! file_exists($logFile)) {
            return;
        }

        file_put_contents($logFile, '');
        $started[] = $logFile;
    }

    private function safeRequest(string $path, array $query): ResourceObject
    {
        $uri = ($this->queryMerger)($path, $query);
        $queryParameter = $uri->query ? '?' . http_build_query($uri->query) : '';
        $url = sprintf('%s%s%s', $this->baseUri, $uri->path, $queryParameter);
        $curl = sprintf('curl -s -i "%s"', $url);

        return $this->request($curl, 'get', $url);
    }

    private function unsafeRequest(string $method, string $path, array $query): ResourceObject
    {
        $uri = ($this->queryMerger)($path, $query);
        $json = json_encode($uri->query, JSON_THROW_ON_ERROR);
        $url = sprintf('%s%s', $this->baseUri, $uri->path);
        $curl = sprintf(
            'curl -s -i -H "Content-Type:application/json" -X %s -d %s "%s"',
            $method,
            escapeshellarg($json),
            $url
        );

        return $this->request($curl, $method, $url);
    }

    private function request(string $curl, string $method, string $url): ResourceObject
    {
        // Try using proc_open first with Windows compatibility fixes
        $output = $this->execWithProcOpen($curl);

        // Fall back to exec if proc_open fails
        if (empty($output)) {
            $output = $this->execWithExec($curl);
        }

        if (empty($output)) {
            throw new RuntimeException('Failed to execute curl command: ' . $curl);
        }

        $uri = new ResourceUri($url);
        $uri->method = $method;
        $ro = ($this->createResponse)($uri, $output);
        $this->log($output, $curl);

        return $ro;
    }

    private function execWithProcOpen(string $curl): array
    {
        $descriptorspec = [
            ['pipe', 'r'],  // stdin
            ['pipe', 'w'],  // stdout
            ['pipe', 'w'],   // stderr
        ];

        $process = proc_open($curl, $descriptorspec, $pipes);
        if (! is_resource($process)) {
            return [];
        }

        // Close stdin as we don't need it
        fclose($pipes[0]);

        $output = [];
        while (! feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line === false) {
                continue;
            }

            $output[] = trim($line);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $output;
    }

    private function execWithExec(string $curl): array
    {
        $output = [];
        exec($curl, $output);

        return $output;
    }

    public function log(array $output, string $curl): void
    {
        $responseLog = implode(PHP_EOL, $output);
        $log = sprintf('%s%s', $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }

    // Interface implementation methods...
    public function get(string $uri, array $query = []): ResourceObject
    {
        return $this->safeRequest($uri, $query);
    }

    public function post(string $uri, array $query = []): ResourceObject
    {
        return $this->unsafeRequest('POST', $uri, $query);
    }

    public function put(string $uri, array $query = []): ResourceObject
    {
        return $this->unsafeRequest('PUT', $uri, $query);
    }

    public function patch(string $uri, array $query = []): ResourceObject
    {
        return $this->unsafeRequest('PATCH', $uri, $query);
    }

    public function delete(string $uri, array $query = []): ResourceObject
    {
        return $this->unsafeRequest('DELETE', $uri, $query);
    }

    // Other interface methods with LogicException...
    public function newInstance($uri): ResourceObject
    {
        throw new LogicException();
    }

    public function object(ResourceObject $ro): RequestInterface
    {
        throw new LogicException();
    }

    public function uri($uri): RequestInterface
    {
        throw new LogicException();
    }

    public function href(string $rel, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    public function head(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    public function options(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }
}
