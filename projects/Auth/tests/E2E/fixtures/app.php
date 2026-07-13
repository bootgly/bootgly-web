<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use function dirname;
use Generator;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\App\Views;


/**
 * Shared server-side handler — reproduces the `Web\App` shell (view
 * conventions + global middleware stack) over the REAL project route set,
 * so every spec exercises the same wire the demo serves.
 */
return static function (Request $Request, Response $Response, Router $Router): Generator {
   static $Views = null;
   static $routes = null;

   $Views ??= new Views;
   $routes ??= require dirname(__DIR__, 3) . '/router/routes/Auth.php';

   $Views->apply($Response);
   $Router->intercept(new SecureHeaders, new RequestId, new BodyParser, new CSRF);

   yield from $routes($Request, $Response, $Router);
};
