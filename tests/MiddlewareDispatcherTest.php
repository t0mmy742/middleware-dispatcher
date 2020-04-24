<?php

declare(strict_types=1);

namespace t0mmy742\Tests\MiddlewareDispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use t0mmy742\MiddlewareDispatcher\MiddlewareDispatcher;

use function sprintf;

class MiddlewareDispatcherTest extends TestCase
{
    use ProphecyTrait;

    public function testEmptyStack(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'MiddlewareDispatcher reaches the end without returning %s.',
                ResponseInterface::class
            )
        );

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $middlewareDispatcher = new MiddlewareDispatcher();
        $middlewareDispatcher->handle($requestProphecy->reveal());
    }

    public function testAddMiddleware(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $response = $responseProphecy->reveal();

        $middlewares = [new class ($response) implements MiddlewareInterface {
            /**
             * @var ResponseInterface
             */
            private $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->response;
            }
        }];
        $middlewareDispatcher = new MiddlewareDispatcher($middlewares);
        $responseResult = $middlewareDispatcher->handle($requestProphecy->reveal());

        $this->assertSame($response, $responseResult);
    }

    public function testAddCallable(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $response = $responseProphecy->reveal();

        $middlewares = [
            function (
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ) use ($response): ResponseInterface {
                return $response;
            }
        ];
        $middlewareDispatcher = new MiddlewareDispatcher($middlewares);
        $responseResult = $middlewareDispatcher->handle($requestProphecy->reveal());

        $this->assertSame($response, $responseResult);
    }

    public function testAddNoMiddleware(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid middleware. Middleware must either be callable or implement %s.',
                MiddlewareInterface::class
            )
        );

        $middlewares = ["bad middleware"];
        new MiddlewareDispatcher($middlewares);
    }

    public function testAddMultipleMiddleware(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $response = $responseProphecy->reveal();

        $middleware1 = new class () implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $middleware2 = new class ($response) implements MiddlewareInterface {
            /**
             * @var ResponseInterface
             */
            private $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->response;
            }
        };

        $middlewares = [$middleware1, $middleware2];
        $middlewareDispatcher = new MiddlewareDispatcher($middlewares);
        $responseResult = $middlewareDispatcher->handle($requestProphecy->reveal());

        $this->assertSame($response, $responseResult);
    }
}
