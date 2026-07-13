<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'A request with ONLY the remember cookie revives the session and rotates the validator',

   request: function () {
      // ! Keep the pre-rotation value for the theft-replay spec.
      State::$stale = State::$remember;

      return State::get('/account', ['remember=' . State::$remember]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 200 || str_contains($response, 'ana@e2e.test') === false) {
         return 'remember revival did not render the account page';
      }

      State::absorb($response);

      // ? Rotation — same series, new validator
      if (State::$remember === State::$stale) {
         return 'remember validator was not rotated on revival';
      }
      if (strtok(State::$remember, '.') !== strtok(State::$stale, '.')) {
         return 'remember series (selector) changed on rotation';
      }

      return true;
   }
);
