<?php

namespace Web\API;

use const DELETE;
use const GET;
use const PATCH;
use const POST;
use const PUT;
use function assert;
use function count;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;

return new Specification(
   description: 'It should expand one REST resource declaration into its route set',
   test: function () {
      // ! Fixture controller — the five REST actions
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
         /** @var array<int,array{0:string,1:callable,2:null|string|array<string>,3:array<mixed>}> */
         public array $records = [];

         public function route (
            string $route,
            callable $handler,
            null|string|array $methods = null,
            array $middlewares = [],
            null|array $cache = null
         ): false
         {
            $this->records[] = [$route, $handler, $methods, $middlewares];
            return false;
         }
      };

      // @ Drain the expansion
      foreach (Routes::map($Spy, '/tasks', $Controller::class) as $_);

      yield assert(
         assertion: count($Spy->records) === 5,
         description: 'A full resource expands into 5 routes'
      );

      // @ Verb → action table
      $expected = [
         ['/tasks', 'list', [GET]],
         ['/tasks', 'create', [POST]],
         ['/tasks/:id<int>', 'show', [GET]],
         ['/tasks/:id<int>', 'update', [PUT, PATCH]],
         ['/tasks/:id<int>', 'delete', [DELETE]]
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

      // @ Constraint customization
      $Spy->records = [];
      foreach (Routes::map($Spy, '/tasks', $Controller::class, only: ['show'], constraint: 'uuid') as $_);

      yield assert(
         assertion: $Spy->records[0][0] === '/tasks/:id<uuid>',
         description: 'The :id constraint is customizable'
      );

      $Spy->records = [];
      foreach (Routes::map($Spy, '/tasks', $Controller::class, only: ['show'], constraint: null) as $_);

      yield assert(
         assertion: $Spy->records[0][0] === '/tasks/:id',
         description: 'A null constraint registers an unconstrained :id'
      );

      // @ Middlewares pass through to every expanded route
      $Middleware = new Problems;
      $Spy->records = [];
      foreach (Routes::map($Spy, '/tasks', $Controller::class, middlewares: [$Middleware]) as $_);

      $passed = true;
      foreach ($Spy->records as $record) {
         $passed = $passed && $record[3] === [$Middleware];
      }

      yield assert(
         assertion: $passed === true,
         description: 'Middlewares are applied to every expanded route'
      );
   }
);
