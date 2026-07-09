<?php

use Bootgly\ABI\Debugging\Data\Vars;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Web\App\Controllers;
use Web\App\tests\E2E\fixtures\Posts;


return new Specification(
   description: 'Controllers::map dispatches GET /posts/:id<int> to show — Route hook reads the param',

   request: function () {
      return "GET /posts/42 HTTP/1.1\r\nHost: localhost\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield from Controllers::map($Router, '/posts', Posts::class);
   },

   test: function ($response) {
      $expected = <<<HTML_RAW
      HTTP/1.1 200 OK\r
      Server: Bootgly\r
      Content-Type: text/html; charset=UTF-8\r
      Content-Length: 13\r
      \r
      posts:show:42
      HTML_RAW;

      // @ Assert
      if ($response !== $expected) {
         Vars::$labels = ['HTTP Response:', 'Expected:'];
         dump(json_encode($response), json_encode($expected));
         return 'Response raw not matched';
      }

      return true;
   }
);
