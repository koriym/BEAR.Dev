<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\NullResourceObject;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri;

use function array_filter;
use function array_shift;
use function implode;
use function json_decode;
use function preg_match;

use const PHP_EOL;

/**
 * Create ResourceObject from curl output
 */
final class CreateResponse
{
    public function __invoke(Uri $uri, array $output): ResourceObject
    {
        if (empty($output)) {
            $ro = new NullResourceObject();
            $ro->uri = $uri;
            $ro->code = 500;
            $ro->body = ['error' => 'Empty response'];
            $ro->view = '{}';

            return $ro;
        }

        $headers = [];
        $body = [];
        $status = (string) array_shift($output);

        // ヘッダー部分の処理
        $headerComplete = false;
        foreach ($output as $line) {
            if (! $headerComplete) {
                if ($line === '') {
                    $headerComplete = true;
                    continue;
                }

                $headers[] = $line;
            } else {
                $body[] = $line;
            }
        }

        $ro = new NullResourceObject();
        $ro->uri = $uri;
        $ro->code = $this->getCode($status);
        $ro->headers = $this->getHeaders($headers);

        // レスポンスボディの処理
        $view = $this->getJsonView($body);
        $ro->body = (array) json_decode($view, true) ?: [];
        $ro->view = $view;

        return $ro;
    }

    private function getCode(string $status): int
    {
        if (empty($status)) {
            return 500;
        }

        if (preg_match('/^HTTP\/\d\.\d\s+(\d{3})/', $status, $match)) {
            return (int) $match[1];
        }

        return 500;
    }

    /**
     * @param array<string> $headers
     *
     * @return array<string, string>
     */
    private function getHeaders(array $headers): array
    {
        $keyedHeader = [];
        foreach ($headers as $header) {
            if (! preg_match('/^([^:]+):\s*(.*)$/', $header, $matches)) {
                continue;
            }

            $keyedHeader[$matches[1]] = $matches[2];
        }

        return $keyedHeader;
    }

    /**
     * @param array<string> $body
     */
    private function getJsonView(array $body): string
    {
        $body = array_filter($body, 'strlen');

        return implode(PHP_EOL, $body);
    }
}
