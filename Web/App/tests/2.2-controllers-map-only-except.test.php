<?php

namespace Web\App;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use InvalidArgumentException;
use Web\API\Action;

return new Specification(
   description: 'It should filter MVC resource actions with only/except and reject unknown names',
   test: function () {
      // ! Fixture controller
      $Controller = new class {
         public function list (object $Request, object $Response): string
         {
            return 'list';
         }

         public function create (object $Request, object $Response): string
         {
            return 'create';
         }

         public function show (object $Request, object $Response): string
         {
            return 'show';
         }

         public function edit (object $Request, object $Response): string
         {
            return 'edit';
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
         public array $registrations = [];

         public function route (
            string $route,
            callable $handler,
            null|string|array $methods = null,
            array $middlewares = [],
            null|array $cache = null
         ): false
         {
            if ($handler instanceof Action) {
               $this->registrations[] = "{$handler->action} {$route}";
            }
            return false;
         }
      };

      // @ only — read-only pages
      foreach (Controllers::map($Spy, '/posts', $Controller::class, only: ['list', 'show']) as $_);

      yield assert(
         assertion: $Spy->registrations === ['list /posts', 'show /posts/:id<int>'],
         description: 'only: expands just the named actions'
      );

      // @ except — no destructive routes
      $Spy->registrations = [];
      foreach (Controllers::map($Spy, '/posts', $Controller::class, except: ['delete']) as $_);

      yield assert(
         assertion: $Spy->registrations === [
            'list /posts',
            'create /posts/create',
            'create /posts',
            'show /posts/:id<int>',
            'edit /posts/:id<int>/edit',
            'update /posts/:id<int>'
         ],
         description: 'except: expands all but the named actions (both delete routes dropped)'
      );

      // @ Unknown action names fail loud
      $guarded = false;
      try {
         foreach (Controllers::map($Spy, '/posts', $Controller::class, except: ['destroy']) as $_);
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
