<?php

use Bootgly\ADI\Databases\SQL;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Test(
   description: 'An immediate duplicate of the pre-rotation value is declined without revocation',

   request: function () {
      // ! Normalize the boundary immediately before the wire request so a
      //   paused or contended CI worker cannot age a benign duplicate past
      //   the private five-second window between cases 2.4 and 2.5.
      $selector = strtok(State::$stale, '.');
      $Database = new SQL(['driver' => 'sqlite', 'database' => State::$database]);
      $Operation = $Database->await($Database->query(
         'UPDATE trusts SET rotated = $1 WHERE selector = $2',
         [time(), $selector]
      ));
      if ($Operation->error !== null || $Operation->affected !== 1) {
         throw new RuntimeException('Remember duplicate fixture could not normalize its grace boundary.');
      }

      return State::get('/account', ['remember=' . State::$stale]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ? A delayed sibling never authenticates — it is treated as a guest.
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'remember duplicate did not answer 303 -> /login';
      }

      // ! Benign grace never clears the caller's cookie; a successful sibling
      //   request may already have delivered the current generation elsewhere.
      if (State::cookie($response, 'remember') !== null || str_contains($response, 'Max-Age=0') === true) {
         return 'remember duplicate cleared the cookie during rotation grace';
      }

      $selector = strtok(State::$stale, '.');
      $Database = new SQL(['driver' => 'sqlite', 'database' => State::$database]);
      $Operation = $Database->await($Database->query(
         'SELECT count(*) AS total FROM trusts WHERE selector = $1',
         [$selector]
      ));
      if ($Operation->error !== null || (int) ($Operation->rows[0]['total'] ?? 0) !== 1) {
         return 'remember duplicate revoked its preserved device series';
      }

      State::absorb($response);

      return true;
   }
);
