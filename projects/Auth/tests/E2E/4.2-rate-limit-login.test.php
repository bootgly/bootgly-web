<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use projects\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'Repeated login failures hit the per-route rate limit with Retry-After',

   requests: [
      function () {
         return State::post('/login', [
            'email' => 'ana@e2e.test',
            'password' => 'totally-wrong-0',
            '_token' => State::$token,
         ], [State::$session]);
      },
      function () {
         return State::post('/login', [
            'email' => 'ana@e2e.test',
            'password' => 'totally-wrong-0',
            '_token' => State::$token,
         ], [State::$session]);
      },
      function () {
         return State::post('/login', [
            'email' => 'ana@e2e.test',
            'password' => 'totally-wrong-0',
            '_token' => State::$token,
         ], [State::$session]);
      },
      function () {
         return State::post('/login', [
            'email' => 'ana@e2e.test',
            'password' => 'totally-wrong-0',
            '_token' => State::$token,
         ], [State::$session]);
      },
      function () {
         return State::post('/login', [
            'email' => 'ana@e2e.test',
            'password' => 'totally-wrong-0',
            '_token' => State::$token,
         ], [State::$session]);
      },
      function () {
         return State::post('/login', [
            'email' => 'ana@e2e.test',
            'password' => 'totally-wrong-0',
            '_token' => State::$token,
         ], [State::$session]);
      }
   ],
   response: require __DIR__ . '/fixtures/app.php',

   test: function (array $responses) {
      $last = $responses[count($responses) - 1];

      // ? The burst must end rate-limited
      if (State::code($last) !== 429) {
         return 'login burst did not end with 429';
      }
      // ?
      if (str_contains($last, 'Retry-After:') === false) {
         return '429 response is missing Retry-After';
      }

      return true;
   }
);
