<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'GET /login renders the sign-in form with a session cookie and CSRF token',

   request: function () {
      return State::get('/login');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 200) {
         return 'GET /login did not answer 200';
      }

      State::absorb($response);
      State::harvest($response);

      // ?
      if (State::$session === '' || State::$token === '') {
         return 'session cookie or CSRF token missing on the login form';
      }

      return true;
   }
);
