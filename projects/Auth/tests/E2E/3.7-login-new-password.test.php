<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /login succeeds with the new password after the reset',

   request: function () {
      return State::post('/login', [
         'email' => 'ana@e2e.test',
         'password' => 'rotated-pass-22',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /account\r\n") === false) {
         return 'login with the new password did not answer 303 -> /account';
      }

      State::absorb($response);

      return true;
   }
);
