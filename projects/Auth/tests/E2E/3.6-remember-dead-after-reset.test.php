<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'The trusted-device cookie no longer revives after the password reset',

   request: function () {
      return State::get('/account', ['remember=' . State::$remember]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ? Trust store was revoked with the old password
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'revoked remember cookie still revived a session';
      }

      return true;
   }
);
