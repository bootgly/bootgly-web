<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\API\Problems;
use Web\App\Views;


/**
 * Shared server-side handler — the `Web\App` shell of `SPA.Project.php`
 * (view conventions + its middleware stack) over the REAL project route set.
 */
return static function (Request $Request, Response $Response, Router $Router): Generator {
   static $Views = null;
   static $routes = null;

   $Views ??= new Views;
   $routes ??= require dirname(__DIR__, 3) . '/router/routes/SPA.routes.php';

   $Views->apply($Response);
   $Router->intercept(new SecureHeaders, new RequestId, new BodyParser, new Problems);

   yield from $routes($Request, $Response, $Router);
};
