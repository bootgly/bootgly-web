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
 * Tasks REST routes.
 *
 * Try it:
 *   curl http://localhost:8090/tasks
 *   curl http://localhost:8090/tasks/1
 *   TOKEN=$(curl -s -X POST http://localhost:8090/auth/token | jq -r .token)
 *   curl -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
 *        -d '{"title":"New task"}' http://localhost:8090/tasks
 */

use function getenv;
use function time;

use Bootgly\API\Security\JWT;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authenticating;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication\JWT as JWTGuard;
use Web\API\Routes;

use Tasks\Controllers\Tasks;


$JWT = new JWT(getenv('JWT_SECRET') ?: 'bootgly-tasks-demo-secret-not-for-production');
$JWTStrategy = new Authenticating(new JWTGuard($JWT));


return static function (Request $Request, Response $Response, Router $Router) use ($JWT, $JWTStrategy): Generator
{
   // * Auth — issue a demo JWT for the mutation routes
   yield $Router->route('/auth/token', function (Request $Request, Response $Response) use ($JWT) {
      $token = $JWT->sign([
         'sub' => 'demo-user',
         'scope' => 'tasks:write',
         'exp' => time() + 3600,
      ]);

      return $Response->JSON->send([
         'token' => $token,
         'authorization' => "Bearer {$token}",
      ]);
   }, POST);

   // * Tasks — reads are public…
   yield from Routes::map($Router, '/tasks', Tasks::class, only: ['list', 'show']);
   // * …mutations require the JWT
   yield from Routes::map(
      $Router, '/tasks', Tasks::class,
      except: ['list', 'show'],
      middlewares: [new Authentication($JWTStrategy)]
   );

   // * Fallback
   yield $Router->route('/*', function (Request $Request, Response $Response) {
      return $Response(code: 404);
   });
};
