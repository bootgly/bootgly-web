<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

/**
 * SPA routes — a JSON API under `/api`, two single-page builds mounted on
 * their own prefixes and a server-rendered landing page at `/`.
 *
 * Try it:
 *   cd projects/Demo/SPA && npm install && npm run build
 *   php bootgly project Demo/SPA start -f
 *   open http://localhost:8092/react/ and http://localhost:8092/vue/
 *   curl http://localhost:8092/api/tasks
 */


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\ETag;
use Web\API\Problem;
use Web\API\Routes;
use Web\App\SPA;
use Web\App\Statics;

use Demo\SPA\Controllers\Tasks;


return static function (Request $Request, Response $Response, Router $Router): Generator
{
   // * API — CRUD over the `Task` model (SQLite)
   yield from Routes::map($Router, '/api/tasks', Tasks::class);
   // ! An unknown API path is a problem+json 404, never a page
   $Unknown = function (Request $Request, Response $Response) {
      throw new Problem(404, detail: 'No API route matches this path.');
   };
   yield $Router->route('/api', $Unknown);
   yield $Router->route('/api/:path*', $Unknown);

   // * Interfaces — one SPA per build, each on its own prefix (Vite `base`)
   foreach (['react', 'vue'] as $name) {
      $SPA = new SPA(
         path: "builds/{$name}",
         aliases: ['favicon.ico' => 'favicon.svg']
      );

      // # The index on the prefix (`/react` and `/react/` alike — the build's
      //   URLs are absolute, Vite `base`), files and client routes under it
      yield $Router->route("/{$name}", $SPA, [GET, HEAD], middlewares: [new ETag]);
      yield $Router->route("/{$name}/:file*", $SPA, [GET, HEAD], middlewares: [new ETag]);
   }

   // * Landing — links to both builds, or the command that makes them
   yield $Router->route('/', function (Request $Request, Response $Response) {
      $base = dirname(__DIR__, 2);

      return $Response->View->render('home', [
         'builds' => [
            'react' => is_file("{$base}/builds/react/index.html"),
            'vue' => is_file("{$base}/builds/vue/index.html"),
         ],
      ], layout: false);
   }, [GET, HEAD]);
   yield $Router->route('/statics/:file*', new Statics, [GET, HEAD]);

   // * Fallback
   yield $Router->route('/*', function (Request $Request, Response $Response) {
      return $Response(code: 404);
   });
};
