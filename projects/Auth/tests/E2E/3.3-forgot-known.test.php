<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /forgot with a known e-mail sinks exactly one recovery mail',

   request: function () {
      State::$mails = State::count();

      return State::post('/forgot', [
         'email' => 'ana@e2e.test',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /forgot\r\n") === false) {
         return 'known-email forgot did not answer 303 -> /forgot';
      }
      // ?
      if (State::count() !== State::$mails + 1) {
         return 'known-email forgot did not sink exactly one mail';
      }

      return true;
   }
);
