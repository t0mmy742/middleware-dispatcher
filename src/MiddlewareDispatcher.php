<?php

declare(strict_types=1);

namespace t0mmy742\MiddlewareDispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use function is_callable;
use function sprintf;

class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * MiddlewareDispatcher constructor.
     *
     * @param mixed[] $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

    /**
     * @param mixed $middleware
     */
    public function add($middleware): void
    {
        if ($middleware instanceof MiddlewareInterface) {
            $this->addMiddleware($middleware);
        } elseif (is_callable($middleware)) {
            $this->addCallable($middleware);
        } else {
            throw new RuntimeException(
                sprintf(
                    'Invalid middleware. Middleware must either be callable or implement %s.',
                    MiddlewareInterface::class
                )
            );
        }
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function addCallable(callable $middleware): void
    {
        $this->middlewares[] = new class ($middleware) implements MiddlewareInterface {
            /**
             * @var callable
             */
            private $middleware;

            public function __construct(callable $middleware)
            {
                $this->middleware = $middleware;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return ($this->middleware)($request, $handler);
            }
        };
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->requestHandlerFromIndex(0)->handle($request);
    }

    private function requestHandlerFromIndex(int $index): RequestHandlerInterface
    {
        $middleware = $this->middlewares[$index] ?? null;
        $next = $middleware != null ? $this->requestHandlerFromIndex($index + 1) : null;

        return new class ($middleware, $next) implements RequestHandlerInterface {
            /**
             * @var MiddlewareInterface|null
             */
            private $middleware;

            /**
             * @var RequestHandlerInterface|null
             */
            private $next;

            public function __construct(?MiddlewareInterface $middleware, ?RequestHandlerInterface $next)
            {
                $this->middleware = $middleware;
                $this->next = $next;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($this->middleware != null && $this->next != null) {
                    return $this->middleware->process($request, $this->next);
                } else {
                    throw new RuntimeException(
                        sprintf(
                            'MiddlewareDispatcher reaches the end without returning %s.',
                            ResponseInterface::class
                        )
                    );
                }
            }
        };
    }
}
