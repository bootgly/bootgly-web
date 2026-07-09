<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\App;


use const DELETE;
use const GET;
use const PATCH;
use const POST;
use const PUT;
use function array_diff;
use function implode;
use function in_array;
use Generator;
use InvalidArgumentException;

use Bootgly\API\Workables\Server\Middleware;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Web\API\Action;


/**
 * MVC resource routing: expands one resource declaration into the
 * HTML-form-aware route set (see `Web\API\Routes` for the REST mapping).
 *
 * | Route             | Methods          | Action   | Notes            |
 * |-------------------|------------------|----------|------------------|
 * | /path             | GET              | `list`   |                  |
 * | /path/create      | GET              | `create` | renders the form |
 * | /path             | POST             | `create` | persists         |
 * | /path/:id         | GET              | `show`   |                  |
 * | /path/:id/edit    | GET              | `edit`   | renders the form |
 * | /path/:id         | POST, PUT, PATCH | `update` | POST = HTML forms|
 * | /path/:id/delete  | POST             | `delete` | HTML forms       |
 * | /path/:id         | DELETE           | `delete` |                  |
 *
 * `create` is the one dual-faced action: GET renders the blank form and
 * POST persists — branch on `$Request->method` inside the action.
 */
abstract class Controllers
{
   // * Metadata
   /** @var array<int,string> */
   private const array ACTIONS = ['list', 'create', 'show', 'edit', 'update', 'delete'];


   /**
    * Expand one MVC resource declaration into its route set.
    *
    * Used inside a route set:
    * `yield from Controllers::map($Router, '/posts', Posts::class);`
    *
    * @param Router $Router The server Router.
    * @param string $path The resource base path (e.g. `/posts`).
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
      $routes = [
         ['list', $path, [GET]],
         ['create', "{$path}/create", [GET]],
         ['create', $path, [POST]],
         ['show', $item, [GET]],
         ['edit', "{$item}/edit", [GET]],
         ['update', $item, [POST, PUT, PATCH]],
         ['delete', "{$item}/delete", [POST]],
         ['delete', $item, [DELETE]]
      ];

      // ? Unknown action names fail loud at registration time
      $unknown = array_diff([...$only ?? [], ...$except ?? []], self::ACTIONS);
      if ($unknown !== []) {
         $names = implode(', ', $unknown);
         throw new InvalidArgumentException("Unknown resource action(s): {$names}.");
      }

      // @
      $Actions = [];
      foreach ($routes as [$action, $route, $methods]) {
         // ? Filtered out
         if ($only !== null && in_array($action, $only, true) === false) {
            continue;
         }
         if ($except !== null && in_array($action, $except, true) === true) {
            continue;
         }

         $Actions[$action] ??= new Action($controller, $action);

         yield $Router->route($route, $Actions[$action], $methods, $middlewares);
      }
   }
}
