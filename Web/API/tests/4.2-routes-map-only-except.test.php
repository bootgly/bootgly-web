<?php

namespace Web\API;

use function array_column;
use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use InvalidArgumentException;

return new Specification(
   description: 'It should filter resource actions with only/except and reject unknown names',
   test: function () {
      // ! Fixture controller
      $Controller = new class {
         public function list (object $Request, object $Response): string
         {
            return 'list';
         }

         public function show (object $Request, object $Response): string
         {
            return 'show';
         }

         public function create (object $Request, object $Response): string
         {
            return 'create';
         }

         public function update (object $Request, object $Response): string
         {
            return 'update';
         }

         public function delete (object $Request, object $Response): string
         {
            return 'delete';
         }
      };

      // ! Router spy
      $Spy = new class extends Router {
         /** @var array<int,string> */
         public array $actions = [];

         public function route (
            string $route,
            callable $handler,
            null|string|array $methods = null,
            array $middlewares = [],
            null|array $cache = null
         ): false
         {
            if ($handler instanceof Action) {
               $this->actions[] = $handler->action;
            }
            return false;
         }
      };

      // @ only
      foreach (Routes::map($Spy, '/tasks', $Controller::class, only: ['list', 'show']) as $_);

      yield assert(
         assertion: $Spy->actions === ['list', 'show'],
         description: 'only: expands just the named actions'
      );

      // @ except
      $Spy->actions = [];
      foreach (Routes::map($Spy, '/tasks', $Controller::class, except: ['list', 'show']) as $_);

      yield assert(
         assertion: $Spy->actions === ['create', 'update', 'delete'],
         description: 'except: expands all but the named actions'
      );

      // @ Unknown action names fail loud
      $guarded = false;
      try {
         foreach (Routes::map($Spy, '/tasks', $Controller::class, only: ['index']) as $_);
      }
      catch (InvalidArgumentException) {
         $guarded = true;
      }

      yield assert(
         assertion: $guarded === true,
         description: 'Unknown action names throw at registration time'
      );
   }
);
