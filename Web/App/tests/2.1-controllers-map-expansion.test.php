<?php

namespace Web\App;

use const DELETE;
use const GET;
use const PATCH;
use const POST;
use const PUT;
use function assert;
use function count;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Web\API\Action;

return new Specification(
   description: 'It should expand one MVC resource declaration into the HTML-form-aware route set',
   test: function () {
      // ! Fixture controller — the six MVC actions
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

      // ! Router spy — records registrations instead of caching them
      $Spy = new class extends Router {
         /** @var array<int,array{0:string,1:callable,2:null|string|array<string>}> */
         public array $records = [];

         public function route (
            string $route,
            callable $handler,
            null|string|array $methods = null,
            array $middlewares = [],
            null|array $cache = null
         ): false
         {
            $this->records[] = [$route, $handler, $methods];
            return false;
         }
      };

      // @ Drain the expansion
      foreach (Controllers::map($Spy, '/posts', $Controller::class) as $_);

      yield assert(
         assertion: count($Spy->records) === 8,
         description: 'A full MVC resource expands into 8 routes'
      );

      // @ Verb → action table (HTML-form-aware)
      $expected = [
         ['/posts', 'list', [GET]],
         ['/posts/create', 'create', [GET]],
         ['/posts', 'create', [POST]],
         ['/posts/:id<int>', 'show', [GET]],
         ['/posts/:id<int>/edit', 'edit', [GET]],
         ['/posts/:id<int>', 'update', [POST, PUT, PATCH]],
         ['/posts/:id<int>/delete', 'delete', [POST]],
         ['/posts/:id<int>', 'delete', [DELETE]]
      ];

      foreach ($expected as $index => [$route, $action, $methods]) {
         $record = $Spy->records[$index];
         $Action = $record[1];

         yield assert(
            assertion: $record[0] === $route
               && $Action instanceof Action
               && $Action->action === $action
               && $Action->controller === $Controller::class
               && $record[2] === $methods,
            description: "Route {$route} [{$action}] registered with its methods"
         );
      }

      // @ Dual-route actions reuse one Action instance
      yield assert(
         assertion: $Spy->records[1][1] === $Spy->records[2][1]
            && $Spy->records[6][1] === $Spy->records[7][1],
         description: 'create/delete dual routes share one Action instance'
      );
   }
);
