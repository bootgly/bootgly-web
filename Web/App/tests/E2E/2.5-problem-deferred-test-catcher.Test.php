<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\API\Problems;
use Web\API\Routes;
use Web\App\tests\E2E\fixtures\Tasks;


return new Test(
   description: 'Test: a generic throwable inside deferred work is declined to the core Catcher',

   request: function () {
      return "GET /tasks HTTP/1.1\r\nHost: localhost\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield from Routes::map(
         $Router, '/tasks', Tasks::class,
         only: ['list'],
         middlewares: [new Problems]
      );
   },

   test: function ($response) {
      if (str_contains($response, 'HTTP/1.1 500') === false) {
         return "Status is not 500: \n" . $response;
      }
      // @ The core Catcher's byte-exact Test body: one space
      if (str_contains($response, 'Content-Length: 1') === false) {
         return "The Catcher's Test body was not answered: \n" . $response;
      }
      // @ Parity with the synchronous rethrow: no problem+json for a generic
      //   Throwable in Test
      if (str_contains($response, 'application/problem+json') === true) {
         return "A generic Throwable was rendered as problem+json in Test: \n" . $response;
      }

      return true;
   }
);
