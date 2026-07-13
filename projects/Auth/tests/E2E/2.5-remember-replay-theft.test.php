<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'Replaying the pre-rotation remember value revokes every device and clears the cookie',

   request: function () {
      return State::get('/account', ['remember=' . State::$stale]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ? Guest treatment — 303 to the sign-in page
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'theft replay did not answer 303 -> /login';
      }

      // ? Clearing Set-Cookie
      if (State::cookie($response, 'remember') !== '' || str_contains($response, 'Max-Age=0') === false) {
         return 'theft replay did not clear the remember cookie';
      }

      State::absorb($response);

      return true;
   }
);
