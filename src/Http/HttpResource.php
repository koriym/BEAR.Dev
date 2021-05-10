<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\Module\ResourceModule;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use LogicException;
use Ray\Di\Injector;

use function array_key_exists;
use function assert;
use function exec;
use function file_exists;
use function file_put_contents;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function parse_url;
use function sprintf;

use const FILE_APPEND;
use const PHP_EOL;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;

final class HttpResource implements ResourceInterface
{
    /** @var ResourceInterface */
    private $resource;

    /** @var string */
    private $logFile = '';

    /** @var string */
    private $baseUri;

    /** @var BuiltinServer */
    private static $server;

    public function __construct(string $host, string $index, string $logFile = 'php://stderr')
    {
        $this->baseUri = sprintf('http://%s', $host);
        $this->logFile = $logFile;
        $this->resetLog($logFile);

        $this->startServer($host, $index);
        $module = new ResourceModule('BEAR/Sunday');
        $this->resource = (new Injector($module))->getInstance(ResourceInterface::class);
    }

    private function startServer(string $host, string $index): void
    {
        /** @var array<string> $started */
        static $started = [];

        $id = $host . $index;
        if (in_array($id, $started)) {
            return;
        }

        self::$server = new BuiltinServer($host, $index);
        self::$server->start();
        $started[] = $id;
    }

    private function resetLog(string $logFile): void
    {
        /** @var array<string> $started */
        static $started = [];

        if (in_array($logFile, $started) || ! file_exists($logFile)) {
            return;
        }

        file_put_contents($logFile, '');
        $started[] = $logFile;
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function newInstance($uri): ResourceObject
    {
        throw new LogicException();
    }

    /**
     * @codeCoverageIgnore
     */
    public function object(ResourceObject $ro): RequestInterface
    {
        throw new LogicException();
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function uri($uri): RequestInterface
    {
        throw new LogicException();
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function href(string $rel, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $uri, array $query = []): ResourceObject
    {
        $httpUri = $this->getHttpUrl($uri);
        $response = $this->resource->{__FUNCTION__}($httpUri, $query);
        $this->safeLog($uri, $query);

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $uri, array $query = []): ResourceObject
    {
        $httpUri = $this->getHttpUrl($uri);
        $response = $this->resource->{__FUNCTION__}($httpUri, $query);
        $this->unsafeLog('POST', $uri, $query);

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $uri, array $query = []): ResourceObject
    {
        $httpUri = $this->getHttpUrl($uri);
        $response = $this->resource->{__FUNCTION__}($httpUri, $query);
        $this->unsafeLog('PUT', $uri, $query);

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function patch(string $uri, array $query = []): ResourceObject
    {
        $httpUri = $this->getHttpUrl($uri);
        $response = $this->resource->{__FUNCTION__}($httpUri, $query);
        $this->unsafeLog('PATCH', $uri, $query);

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $uri, array $query = []): ResourceObject
    {
        $httpUri = $this->getHttpUrl($uri);
        $response = $this->resource->{__FUNCTION__}($httpUri, $query);
        $this->unsafeLog('DELETE', $uri, $query);

        return $response;
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function head(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function options(string $uri, array $query = []): ResourceObject
    {
        throw new LogicException();
    }

    /**
     * @param array<string> $query
     */
    private function safeLog(string $uri, array $query): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query += (array) parse_url($uri, PHP_URL_QUERY);
        $queryParameter = $query ? '?' . http_build_query($query) : '';
        $curl = sprintf("curl -s -i '%s%s%s'", $this->baseUri, $path, $queryParameter);
        exec($curl, $output);
        $responseLog = implode(PHP_EOL, $output);
        $log = sprintf("%s\n\n%s", $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }

    /**
     * @param array<string> $query
     */
    private function unsafeLog(string $method, string $uri, array $query): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $query += (array) parse_url($uri, PHP_URL_QUERY);
        $json = json_encode($query);
        $curl = sprintf("curl -s -i -H 'Content-Type:application/json' -X %s -d '%s' %s%s", $method, $json, $this->baseUri, $path);
        exec($curl, $output);
        $responseLog = implode(PHP_EOL, $output);
        $log = sprintf("%s\n\n%s", $curl, $responseLog) . PHP_EOL . PHP_EOL;
        file_put_contents($this->logFile, $log, FILE_APPEND);
    }

    public function getHttpUrl(string $uri): string
    {
        $pUri = parse_url($uri);
        assert(is_array($pUri));
        assert(array_key_exists('path', $pUri));
        if (array_key_exists('scheme', $pUri) && ($pUri['scheme'] === 'http' || $pUri['scheme'] === 'https')) {
            return $uri;
        }

        $query = isset($pUri['query']) ? sprintf('?%s', $pUri['query']) : '';

        return sprintf('%s%s%s', $this->baseUri, $pUri['path'], $query);
    }
}
