<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\SPA;


return new Test(
   description: 'SPA answers 503 with a neutral body while the build is not deployed',

   requests: [
      static fn (): string => "GET / HTTP/1.1\r\nHost: localhost\r\n\r\n",
      static fn (): string => "GET /creative/library HTTP/1.1\r\nHost: localhost\r\n\r\n",
   ],
   response: function (Request $Request, Response $Response, Router $Router) {
      $SPA = new SPA(path: 'spa-not-deployed');

      yield $Router->route('/', $SPA, [GET, HEAD]);
      yield $Router->route('/:file*', $SPA, [GET, HEAD]);
   },

   test: function (array $responses) {
      foreach ($responses as $response) {
         if (str_starts_with($response, 'HTTP/1.1 503 ') === false) {
            return "Status is not 503: \n" . $response;
         }
         if (str_contains($response, "Content-Type: text/plain; charset=UTF-8\r\n") === false) {
            return "Media type is not text/plain: \n" . $response;
         }
         if (str_contains($response, "Cache-Control: no-store\r\n") === false) {
            return "A 503 must not be cached: \n" . $response;
         }
         if (str_ends_with($response, "\r\n\r\nService Unavailable") === false) {
            return "Body is not the neutral status text: \n" . $response;
         }
      }

      return count($responses) === 2 ? true : 'Not every request was answered';
   }
);
