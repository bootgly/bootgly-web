<?php

namespace Web\API;


use function assert;
use function is_string;
use function json_decode;
use function str_contains;
use RuntimeException;

use Bootgly\ABI\Debugging\Data\Throwables;
use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\API\Environments;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


return new Specification(
   description: 'It should render thrown Problems and guard generic throwables per environment',
   test: function () {
      // ! Fixtures
      $Problems = new Problems;
      $Request = new class {
         public string $method = 'GET';
         public string $URI = '/tasks/1';
         public string $peer = '127.0.0.1';
      };

      // @ Success — pass-through
      $Response = new Response;
      $Returned = $Problems->process($Request, $Response, function ($Request, $Response) {
         return $Response;
      });

      yield assert(
         assertion: $Returned === $Response,
         description: 'Successful handlers pass through untouched'
      );

      // @ Thrown Problem — rendered in every environment
      $Response = new Response;
      $Returned = $Problems->process($Request, $Response, function ($Request, $Response) {
         throw new Problem(404, detail: 'Task 1 not found');
      });

      $body = $Returned->Body->raw;
      $members = json_decode(is_string($body) ? $body : '', true);

      yield assert(
         assertion: $Returned->code === 404
            && $Returned->Header->get('Content-Type') === 'application/problem+json'
            && $members['detail'] === 'Task 1 not found',
         description: 'A thrown Problem renders as problem+json'
      );

      // @ Generic throwable in Production — reported + internals-free 500 problem
      $reporters = Throwables::$reporters;
      $reported = [];
      Throwables::$reporters = [
         function ($Throwable, $context) use (&$reported): void {
            $reported[] = [$Throwable, $context];
         }
      ];

      try {
         Problems::$Environment = Environments::Production;

         $Response = new Response;
         $Returned = $Problems->process($Request, $Response, function ($Request, $Response) {
            throw new RuntimeException('secret internals');
         });

         $body = $Returned->Body->raw;
         $members = json_decode(is_string($body) ? $body : '', true);

         yield assert(
            assertion: $Returned->code === 500
               && $members['status'] === 500
               && str_contains(is_string($body) ? $body : '', 'secret internals') === false,
            description: 'Production: generic throwable becomes an internals-free 500 problem'
         );
         yield assert(
            assertion: isSet($reported[0])
               && $reported[0][0] instanceof RuntimeException
               && $reported[0][1]['interface'] === 'WPI'
               && $reported[0][1]['URI'] === '/tasks/1',
            description: 'Production: the throwable is reported through Throwables::notify'
         );
         yield assert(
            assertion: Problems::$Environment === null,
            description: 'The one-shot environment override is consumed'
         );

         // @ Generic throwable in Development — rethrown to the core Catcher
         Problems::$Environment = Environments::Development;

         $rethrown = false;
         try {
            $Problems->process($Request, new Response, function ($Request, $Response) {
               throw new RuntimeException('boom');
            });
         }
         catch (RuntimeException) {
            $rethrown = true;
         }

         yield assert(
            assertion: $rethrown === true,
            description: 'Development: generic throwables are rethrown to the core Catcher'
         );
      }
      finally {
         Throwables::$reporters = $reporters;
         Problems::$Environment = null;
      }
   }
);
