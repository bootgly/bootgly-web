<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'GET on the e-mailed reset link renders the new-password form (non-consuming peek)',

   request: function () {
      return State::get(State::link('reset'), [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 200 || str_contains($response, 'Choose a new password') === false) {
         return 'reset link did not render the new-password form';
      }

      State::harvest($response);

      return true;
   }
);
