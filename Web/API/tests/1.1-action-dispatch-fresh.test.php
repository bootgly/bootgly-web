<?php

namespace Web\API;

use function assert;
use function is_callable;
use Closure;
use stdClass;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should dispatch actions with a fresh controller instance per request',
   test: function () {
      // ! Fixture controller — counts constructions
      $Fixture = new class {
         public static int $instances = 0;

         public function __construct ()
         {
            self::$instances++;
         }

         public function ping (object $Request, object $Response): string
         {
            return 'pong';
         }
      };
      $controller = $Fixture::class;
      $Fixture::$instances = 0;

      // ! Action
      $Action = new Action($controller, 'ping');

      // @ Callable, but never a Closure (the Router only rebinds Closures)
      yield assert(
         assertion: is_callable($Action) === true && $Action instanceof Closure === false,
         description: 'Action is callable and is not a Closure'
      );

      // @ Registration state
      yield assert(
         assertion: $Action->controller === $controller && $Action->action === 'ping',
         description: 'Action exposes its controller and action'
      );

      // @ Dispatch
      $Request = new stdClass;
      $Response = new stdClass;

      $first = $Action($Request, $Response);
      $second = $Action($Request, $Response);

      yield assert(
         assertion: $first === 'pong' && $second === 'pong',
         description: 'Dispatch calls the action and returns its result'
      );

      // @ Fresh instance per request
      yield assert(
         assertion: $Fixture::$instances === 2,
         description: 'Each dispatch constructs a fresh controller instance'
      );
   }
);
