<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test\Specification;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Specification(
   description: 'POST /login with remember=1 issues a hardened trusted-device cookie',

   request: function () {
      return State::post('/login', [
         'email' => 'ana@e2e.test',
         'password' => 'initial-pass-1',
         'remember' => '1',
         '_token' => State::$token,
      ], [State::$session]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ?
      if (State::code($response) !== 303 || str_contains($response, "Location: /account\r\n") === false) {
         return 'login did not answer 303 -> /account';
      }

      // ? Remember cookie: selector.validator + hardened flags
      if (preg_match('#Set-Cookie: remember=[a-f0-9]{16}\.[a-f0-9]{64}; Max-Age=2592000; Path=/; Secure; HttpOnly; SameSite=Lax#', $response) !== 1) {
         return 'remember Set-Cookie missing or missing hardened flags';
      }

      State::absorb($response);

      return true;
   }
);
