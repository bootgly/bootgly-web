<?php

use function str_contains;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Web\API\Problems;
use Web\API\Routes;
use Web\App\tests\E2E\fixtures\Tasks;


return new Specification(
   description: 'Production: a generic throwable becomes an internals-free 500 problem',

   request: function () {
      return "PUT /tasks/13 HTTP/1.1\r\nHost: localhost\r\nContent-Length: 0\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield from Routes::map(
         $Router, '/tasks', Tasks::class,
         only: ['update'],
         middlewares: [new Problems]
      );
   },

   test: function ($response) {
      if (str_contains($response, 'HTTP/1.1 500') === false) {
         return "Status is not 500: \n" . $response;
      }
      if (str_contains($response, 'Content-Type: application/problem+json') === false) {
         return "Media type is not application/problem+json: \n" . $response;
      }
      if (str_contains($response, '"status":500') === false) {
         return "Problem status member not matched: \n" . $response;
      }
      // @ Never leak internals in Production
      if (str_contains($response, 'secret internals') === true) {
         return "Internals leaked into the Production problem: \n" . $response;
      }

      return true;
   }
);
