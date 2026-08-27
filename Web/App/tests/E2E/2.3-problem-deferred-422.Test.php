<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\API\Problems;
use Web\API\Routes;
use Web\App\tests\E2E\fixtures\Tasks;


return new Test(
   description: 'A Problem thrown inside deferred work renders as application/problem+json',

   request: function () {
      return "DELETE /tasks/13 HTTP/1.1\r\nHost: localhost\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield from Routes::map(
         $Router, '/tasks', Tasks::class,
         only: ['delete'],
         middlewares: [new Problems]
      );
   },

   test: function ($response) {
      if (str_contains($response, 'HTTP/1.1 422') === false) {
         return "Status is not 422: \n" . $response;
      }
      if (str_contains($response, 'Content-Type: application/problem+json') === false) {
         return "Media type is not application/problem+json: \n" . $response;
      }
      if (str_contains($response, '"detail":"Task 13 cannot be deleted"') === false) {
         return "Problem detail member not matched: \n" . $response;
      }
      if (str_contains($response, '"status":422') === false) {
         return "Problem status member not matched: \n" . $response;
      }

      return true;
   }
);
