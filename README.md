# Middleware Dispatcher (PSR-15)

[![Build Status](https://travis-ci.org/t0mmy742/middleware-dispatcher.svg?branch=master)](https://travis-ci.org/t0mmy742/middleware-dispatcher)
[![Coverage Status](https://coveralls.io/repos/github/t0mmy742/middleware-dispatcher/badge.svg?branch=master)](https://coveralls.io/github/t0mmy742/middleware-dispatcher?branch=master)

A simple PSR-15 middleware dispatcher mainly used for testing.

## Installation

```bash
$ composer require t0mmy742/middleware-dispatcher
```

## Usage

```php
<?php

use t0mmy742\MiddlewareDispatcher;

$request = new \Your\PSR7\ServerRequest();
$middleware1 = new \Your\First\PSR15\Middleware();
$middleware2 = new \Your\Second\PSR15\Middleware();
$middlewares = [$middleware1, $middleware2];
$middlewareDispatcher = new MiddlewareDispatcher($middlewares);
$response = $middlewareDispatcher->handle($request);
```
or
```php
<?php

use t0mmy742\MiddlewareDispatcher;

$request = new \Your\PSR7\ServerRequest();
$middlewareDispatcher = new MiddlewareDispatcher();
$middlewareDispatcher->add(new \Your\First\PSR15\Middleware());
$middlewareDispatcher->add(new \Your\Second\PSR15\Middleware());
$response = $middlewareDispatcher->handle($request);
```

If the last middleware can't return a PSR-7 Response, it will throw a RuntimeException.