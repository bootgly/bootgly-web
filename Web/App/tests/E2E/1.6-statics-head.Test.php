<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\Statics;


return new Test(
   description: 'Statics answers HEAD with the GET headers and no body',

   request: function () {
      return "HEAD /statics/app.css HTTP/1.1\r\nHost: localhost\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield $Router->route('/statics/:file*', new Statics, [GET, HEAD]);
   },

   test: function ($response) {
      $length = filesize(__DIR__ . '/fixtures/statics/app.css');

      if (str_starts_with($response, 'HTTP/1.1 200 OK') === false) {
         return "Status is not 200 OK: \n" . $response;
      }
      if (str_contains($response, "Content-Type: text/css; charset=UTF-8\r\n") === false) {
         return "Media type is not text/css: \n" . $response;
      }
      if (str_contains($response, "Cache-Control: public, max-age=3600\r\n") === false) {
         return "Cache-Control not matched: \n" . $response;
      }
      if (str_contains($response, "Content-Length: {$length}\r\n") === false) {
         return "Content-Length is not the file size ({$length}): \n" . $response;
      }
      if (str_ends_with($response, "\r\n\r\n") === false) {
         return "HEAD must not carry a body: \n" . $response;
      }

      return true;
   }
);
