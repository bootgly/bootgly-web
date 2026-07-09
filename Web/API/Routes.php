<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\API;


use const DELETE;
use const GET;
use const PATCH;
use const POST;
use const PUT;
use function array_diff;
use function array_keys;
use function implode;
use function in_array;
use Generator;
use InvalidArgumentException;

use Bootgly\API\Workables\Server\Middleware;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;


/**
 * REST resource routing: expands one resource declaration into its
 * canonical route set (no HTML form pages — see `Web\App\Controllers`
 * for the MVC mapping).
 *
 * | Route        | Methods    | Action   |
 * |--------------|------------|----------|
 * | /path        | GET        | `list`   |
 * | /path        | POST       | `create` |
 * | /path/:id    | GET        | `show`   |
 * | /path/:id    | PUT, PATCH | `update` |
 * | /path/:id    | DELETE     | `delete` |
 */
abstract class Routes
{
   /**
    * Expand one REST resource declaration into its route set.
    *
    * Used inside a route set:
    * `yield from Routes::map($Router, '/tasks', Tasks::class);`
    *
    * Per-action middleware is two declarations — the canonical pattern:
    * `yield from Routes::map($Router, '/tasks', Tasks::class, only: ['list', 'show']);`
    * `yield from Routes::map($Router, '/tasks', Tasks::class, except: ['list', 'show'], middlewares: [$Auth]);`
    *
    * @param Router $Router The server Router.
    * @param string $path The resource base path (e.g. `/tasks`).
    * @param class-string $controller The resource controller class.
    * @param null|array<string> $only Expand only these actions.
    * @param null|array<string> $except Expand all but these actions.
    * @param array<Middleware> $middlewares Applied to every expanded route.
    * @param null|string $constraint Param constraint for `:id` (`int` default; null = none).
    *
    * @throws InvalidArgumentException When `$only`/`$except` name unknown actions.
    */
   public static function map (
      Router $Router,
      string $path,
      string $controller,
      null|array $only = null,
      null|array $except = null,
      array $middlewares = [],
      null|string $constraint = 'int'
   ): Generator
   {
      // !
      $id = $constraint === null ? ':id' : ":id<{$constraint}>";
      $item = "{$path}/{$id}";
      $actions = [
         'list' => [$path, [GET]],
         'create' => [$path, [POST]],
         'show' => [$item, [GET]],
         'update' => [$item, [PUT, PATCH]],
         'delete' => [$item, [DELETE]]
      ];

      // ? Unknown action names fail loud at registration time
      $unknown = array_diff([...$only ?? [], ...$except ?? []], array_keys($actions));
      if ($unknown !== []) {
         $names = implode(', ', $unknown);
         throw new InvalidArgumentException("Unknown resource action(s): {$names}.");
      }

      // @
      foreach ($actions as $action => [$route, $methods]) {
         // ? Filtered out
         if ($only !== null && in_array($action, $only, true) === false) {
            continue;
         }
         if ($except !== null && in_array($action, $except, true) === true) {
            continue;
         }

         yield $Router->route($route, new Action($controller, $action), $methods, $middlewares);
      }
   }
}
