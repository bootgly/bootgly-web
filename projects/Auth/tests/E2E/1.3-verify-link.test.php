<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'GET on the e-mailed verification link redeems the single-use token',

   request: function () {
      // ! The worker sank the rendered mail to storage/mails/ — read the link back.
      return State::get(State::link('verify'), [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /account\r\n") === false) {
         return 'verification link did not answer 303 -> /account';
      }

      return true;
   }
);
