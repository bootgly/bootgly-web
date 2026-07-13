<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

/**
 * Blog routes — the full MVC resource plus commons.
 */

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Web\App\Controllers;
use Web\App\Statics;

use Blog\Controllers\Posts;


return static function (Request $Request, Response $Response, Router $Router): Generator
{
   // * Posts — the full MVC resource:
   //   GET /posts, GET/POST /posts/create|/posts, GET /posts/:id,
   //   GET /posts/:id/edit, POST|PUT|PATCH /posts/:id,
   //   POST /posts/:id/delete, DELETE /posts/:id
   yield from Controllers::map($Router, '/posts', Posts::class);

   // * Commons
   yield $Router->route('/', function (Request $Request, Response $Response) {
      return $Response->redirect('/posts', 307);
   }, GET);

   yield $Router->route('/statics/:file*', new Statics, GET);

   // * Fallback
   yield $Router->route('/*', function (Request $Request, Response $Response) {
      $Response->View->render('errors/404');

      return $Response->code(404);
   }, GET);
};
