<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /login with a wrong password fails uniformly back to the form',

   request: function () {
      return State::post('/login', [
         'email' => 'ana@e2e.test',
         'password' => 'wrong-pass-000',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ? Uniform failure — plain 303 back to the form, no oracle
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'wrong-password login did not answer 303 -> /login';
      }

      return true;
   }
);
