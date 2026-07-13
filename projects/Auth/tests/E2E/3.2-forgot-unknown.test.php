<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /forgot with an unknown e-mail answers uniformly and sends nothing',

   request: function () {
      State::$mails = State::count();

      return State::post('/forgot', [
         'email' => 'ghost@e2e.test',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /forgot\r\n") === false) {
         return 'unknown-email forgot did not answer 303 -> /forgot';
      }
      // ? Anti-enumeration — no mail may be produced
      if (State::count() !== State::$mails) {
         return 'unknown-email forgot produced a mail';
      }

      return true;
   }
);
