<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /login without the CSRF token is rejected with 403',

   request: function () {
      return State::post('/login', [
         'email' => 'ana@e2e.test',
         'password' => 'rotated-pass-22',
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 403) {
         return 'POST without CSRF token was not rejected with 403';
      }

      return true;
   }
);
