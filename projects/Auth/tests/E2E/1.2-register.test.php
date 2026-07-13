<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /register creates the account and regenerates the session id',

   request: function () {
      return State::post('/register', [
         'email' => 'ana@e2e.test',
         'password' => 'initial-pass-1',
         'password_confirmation' => 'initial-pass-1',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /account\r\n") === false) {
         return 'registration did not answer 303 -> /account';
      }

      // ? Session fixation defense — the id MUST change on registration
      $before = State::$session;
      State::absorb($response);
      if (State::$session === $before) {
         return 'session id was not regenerated on registration';
      }

      return true;
   }
);
