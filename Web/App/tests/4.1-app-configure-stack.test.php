<?php

namespace Web\App;

use function assert;
use function count;
use Exception;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\App;

return new Specification(
   description: 'It should compose the opinionated shell over the canonical HTTP Server',
   test: function () {
      // ! App — Test mode keeps the server unbound
      $App = new App(Mode: Modes::Test);

      // @ The shell owns a real HTTP_Server_CLI + Views conventions
      yield assert(
         assertion: $App->Server instanceof HTTP_Server_CLI && $App->Views instanceof Views,
         description: 'App wraps the canonical HTTP_Server_CLI and the Views conventions'
      );

      // @ Default middleware stack — order matters
      yield assert(
         assertion: count($App->Middlewares) === 4
            && $App->Middlewares[0] instanceof SecureHeaders
            && $App->Middlewares[1] instanceof RequestId
            && $App->Middlewares[2] instanceof BodyParser
            && $App->Middlewares[3] instanceof CSRF,
         description: 'Default stack: SecureHeaders, RequestId, BodyParser, CSRF'
      );

      // @ configure(middlewares:) replaces the stack wholesale
      $CSRF = new CSRF;
      $Returned = $App->configure(port: 8098, workers: 1, middlewares: [$CSRF]);

      yield assert(
         assertion: $Returned === $App && $App->Middlewares === [$CSRF],
         description: 'configure() is chainable and replaces the middleware stack wholesale'
      );

      // @ start() without a loaded router fails loud
      $guarded = false;
      try {
         $App->start();
      }
      catch (Exception) {
         $guarded = true;
      }

      yield assert(
         assertion: $guarded === true,
         description: 'start() without load() throws instead of serving nothing'
      );
   }
);
