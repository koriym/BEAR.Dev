<?php

declare(strict_types=1);

namespace BEAR\Dev\Http;

use BEAR\Resource\NullResourceObject;
use BEAR\Resource\ResourceObject;

use function array_key_exists;
use function array_pop;
use function array_shift;
use function assert;
use function implode;
use function json_decode;
use function preg_match;

use const PHP_EOL;

final class CreateResponse
{
    public function __invoke(string $pathQuery, array $output): ResourceObject
    {
        $headers = $body = [];
        $status = array_shift($output);
        do {
            $line = array_shift($output);
            $headers[] = $line;
        } while ($line !== '');

        do {
            $line = array_shift($output);
            $body[] = $line;
        } while ($line !== null);

        array_pop($headers);
        array_pop($body);
        preg_match('/\d{3}/', $status, $match);
        assert(array_key_exists(0, $match));
        $jsonBody = implode(PHP_EOL, $body);
        $code = $match[0];
        $ro = new NullResourceObject();
        $ro->uri = $pathQuery;
        $ro->code = (int) $code;
        $ro->headers = $headers;
        $ro->body = (array) json_decode($jsonBody);
        $ro->view = $jsonBody;

        return $ro;
    }
}
