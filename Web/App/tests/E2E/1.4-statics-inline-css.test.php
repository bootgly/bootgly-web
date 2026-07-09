<?php

use function str_contains;

use const GET;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Web\App\Statics;


return new Specification(
   description: 'Statics serves project assets inline with the right media type',

   request: function () {
      return "GET /statics/app.css HTTP/1.1\r\nHost: localhost\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield $Router->route('/statics/:file*', new Statics, GET);
   },

   test: function ($response) {
      if (str_contains($response, '200 OK') === false) {
         return "Status is not 200 OK: \n" . $response;
      }
      // @ Inline stylesheet media type — never attachment/octet-stream
      if (str_contains($response, 'Content-Type: text/css; charset=UTF-8') === false) {
         return "Media type is not text/css: \n" . $response;
      }
      if (str_contains($response, 'Content-Disposition') === true) {
         return "Statics must serve inline (no Content-Disposition): \n" . $response;
      }
      if (str_contains($response, 'body { color: green; }') === false) {
         return "Stylesheet body not matched: \n" . $response;
      }

      return true;
   }
);
