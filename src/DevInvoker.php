<?php

declare(strict_types=1);

namespace BEAR\Dev;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;
use Ray\Aop\WeavedInterface;
use Ray\Di\Di\Named;

use function get_class;
use function json_encode;
use function memory_get_usage;
use function microtime;

final class DevInvoker implements InvokerInterface
{
    public const HEADER_INTERCEPTORS = 'x-interceptors';

    public const HEADER_EXECUTION_TIME = 'x-execution-time';

    public const HEADER_MEMORY_USAGE = 'x-memory-usage';

    public const HEADER_PROFILE_ID = 'x-profile-id';

    public const HEADER_PARAMS = 'x-params';

    public const HEADER_QUERY = 'x-query';

    public function __construct(
        #[Named('original')]
        private InvokerInterface $invoker
    ) {
    }

    public function invoke(AbstractRequest $request): ResourceObject
    {
        $resource = $this->getRo($request);

        if ($request->method === Request::OPTIONS || $request->method === Request::HEAD) {
            return $this->invoker->invoke($request);
        }

        return $this->devInvoke($resource, $request);
    }

    private function devInvoke(ResourceObject $resource, AbstractRequest $request)
    {
        $resource->headers[self::HEADER_QUERY] = json_encode($request->query);
        $time = microtime(true);
        $memory = memory_get_usage();

        $result = $this->invoker->invoke($request);

        // post process for log
        $resource->headers[self::HEADER_EXECUTION_TIME] = microtime(true) - $time;
        $resource->headers[self::HEADER_MEMORY_USAGE] = memory_get_usage() - $memory;

        return $result;
    }

    private function getRo(AbstractRequest $request): ResourceObject
    {
        if (! $request->resourceObject instanceof WeavedInterface) {
            return $request->resourceObject;
        }

        $bind = $request->resourceObject->bindings;
        $interceptors = $this->getBindInfo($bind);
        $request->resourceObject->headers[self::HEADER_INTERCEPTORS] = json_encode($interceptors);
//        $ro->headers[self::HEADER_INTERCEPTORS] = json_encode($interceptors);

        return $request->resourceObject;
    }

    /**
     * @return array
     */
    public function getBindInfo(array $bindgs): array
    {
        $interceptorInfo = [];
        foreach ($bindgs as $method => $interceptors) {
            $interceptorNames = [];
            foreach ($interceptors as &$interceptor) {
                $interceptorNames[] = get_class($interceptor);
            }

            $interceptorInfo[$method] = $interceptorNames;
        }

        return $interceptorInfo;
    }
}
