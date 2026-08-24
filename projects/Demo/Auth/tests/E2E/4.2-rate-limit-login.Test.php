<?php

use Bootgly\ABI\Resources\Cache;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Demo\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Test(
   description: 'Repeated login failures hit the per-route rate limit with Retry-After',

   requests: [
      function () {
         // ! Earlier authentication specs legitimately exercised /login.
         //   Reset only this run's private segment before measuring the burst.
         $Cache = new Cache([
            'driver' => 'shared',
            'prefix' => 'ratelimit:',
            'segment' => State::$segment,
         ]);
         $Cache->clear();

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
      $Cache = new Cache([
         'driver' => 'shared',
         'prefix' => 'ratelimit:',
         'segment' => State::$segment,
      ]);
      try {
         $codes = array_map(
            static fn (string $response): int => State::code($response),
            $responses
         );
         $last = $responses[count($responses) - 1];

         // ? Exactly five requests pass the route policy; the sixth is blocked.
         if ($codes !== [303, 303, 303, 303, 303, 429]) {
            return 'login burst status sequence was ' . json_encode($codes);
         }
         // ?
         if (str_contains($last, 'Retry-After:') === false) {
            return '429 response is missing Retry-After';
         }

         return true;
      }
      finally {
         // @ Leave the private policy segment clean even when an assertion
         //   fails, so the final login reports its own outcome rather than a
         //   quota exhausted by this diagnostic burst.
         $Cache->clear();
      }
   }
);
