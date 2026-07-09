<?php

use function str_contains;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Web\API\Action;
use Web\App\tests\E2E\fixtures\Pages;


return new Specification(
   description: 'Controller->render() renders a fixture view wrapped by the layout',

   request: function () {
      return "GET /about HTTP/1.1\r\nHost: localhost\r\n\r\n";
   },
   response: function (Request $Request, Response $Response, Router $Router) {
      yield $Router->route('/about', new Action(Pages::class, 'show'), GET);
   },

   test: function ($response) {
      if (str_contains($response, '200 OK') === false) {
         return "Status is not 200 OK: \n" . $response;
      }
      // @ The view body lands inside the layout `content` section
      if (str_contains($response, '<l><article>About Bootgly Web</article>') === false) {
         return "Layout-wrapped view body not matched: \n" . $response;
      }

      return true;
   }
);
