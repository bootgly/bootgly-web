<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'GET /account shows the verified badge after the link is consumed',

   request: function () {
      return State::get('/account', [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 200) {
         return 'GET /account did not answer 200';
      }
      // ?
      if (str_contains($response, 'ana@e2e.test') === false || str_contains($response, 'verified') === false) {
         return 'account page does not show the verified e-mail';
      }
      // ? The unverified banner must be gone
      if (str_contains($response, 'Resend verification link')) {
         return 'account page still offers the resend banner after verification';
      }

      State::harvest($response);

      return true;
   }
);
