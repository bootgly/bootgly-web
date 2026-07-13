<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'GET /login refreshes the session cookie and CSRF token after logout',

   request: function () {
      return State::get('/login', [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 200) {
         return 'GET /login did not answer 200';
      }

      State::absorb($response);
      State::harvest($response);

      return true;
   }
);
