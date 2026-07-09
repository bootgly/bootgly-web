<?php

namespace Web\App;

use function assert;

use const Bootgly\WPI;
use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;

return new Specification(
   description: 'It should expose the matched route and the response helpers',
   test: function () {
      // ! Bind fresh Router/Response onto the WPI holder
      $WPI = WPI;
      $WPI->Router = new Router;
      $WPI->Response = new Response;

      // ! Fixture controller — exposes the protected helpers
      $Controller = new class extends Controller {
         public function bounce (string $URI): Response
         {
            // : Explicit code — the null-code default reads the live request
            //   method (POST → 303), which only exists on a running server
            return $this->redirect($URI, 303);
         }
      };

      // @ Route hook reads the live matched route
      yield assert(
         assertion: $Controller->Route === $WPI->Router->Route,
         description: 'Controller->Route reads the current matched route from the server'
      );

      // @ redirect() drives the bound Response
      $Redirected = $Controller->bounce('/posts');

      yield assert(
         assertion: $Redirected === $WPI->Response,
         description: 'redirect() returns the bound Response'
      );
      yield assert(
         assertion: $Redirected->Header->get('Location') === '/posts' && $Redirected->code === 303,
         description: 'redirect() sets the Location header and the status code'
      );
   }
);
