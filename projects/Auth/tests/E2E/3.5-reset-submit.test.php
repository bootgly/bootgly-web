<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST on the reset link rotates the password and burns the token',

   request: function () {
      return State::post(State::link('reset'), [
         'password' => 'rotated-pass-22',
         'password_confirmation' => 'rotated-pass-22',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'reset submit did not answer 303 -> /login';
      }

      return true;
   }
);
