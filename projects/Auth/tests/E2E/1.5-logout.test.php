<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /logout flushes the session and clears the trusted device',

   request: function () {
      return State::post('/logout', ['_token' => State::$token], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'logout did not answer 303 -> /login';
      }

      State::absorb($response);

      return true;
   }
);
