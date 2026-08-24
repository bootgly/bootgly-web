<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Demo\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Test(
   description: 'GET /forgot renders the recovery form',

   request: function () {
      return State::get('/forgot', [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 200) {
         return 'GET /forgot did not answer 200';
      }

      State::absorb($response);
      State::harvest($response);

      return true;
   }
);
