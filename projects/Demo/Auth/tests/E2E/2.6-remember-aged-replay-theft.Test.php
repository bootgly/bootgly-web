<?php

use Bootgly\ADI\Databases\SQL;
use Bootgly\API\Security\Tokens\Token;
use Bootgly\API\Security\Tokens\Trust;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Demo\Auth\tests\E2E\fixtures\State;

require_once __DIR__ . '/fixtures/State.php';

return new Test(
   description: 'Replaying an aged previous remember value revokes its user and clears the cookie',

   request: function () {
      // ! Build a separate series for the seeded demo user. Freezing its
      //   rotation six seconds in the past proves the late-replay branch
      //   deterministically without sleeping or disturbing Ana's live series.
      $Database = new SQL(['driver' => 'sqlite', 'database' => State::$database]);
      $Trust = new Trust($Database);
      $Trust->freeze(time() - 6);

      $previousGC = Trust::$gcProbability;
      Trust::$gcProbability = [0, 1];
      try {
         $Old = $Trust->issue('1');
         $Current = $Trust->rotate($Old->value);
      }
      finally {
         Trust::$gcProbability = $previousGC;
      }

      if ($Current instanceof Token === false) {
         throw new RuntimeException('Aged remember replay fixture could not rotate its device series.');
      }

      State::$aged = $Old->value;

      return State::get('/account', ['remember=' . State::$aged]);
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      // ? Late replay is a theft verdict and never authenticates.
      if (State::code($response) !== 303 || str_contains($response, "Location: /login\r\n") === false) {
         return 'aged remember replay did not answer 303 -> /login';
      }

      // ? Theft clears the presented remember value.
      if (State::cookie($response, 'remember') !== '' || str_contains($response, 'Max-Age=0') === false) {
         return 'aged remember replay did not clear the remember cookie';
      }

      // @ Only the affected demo user is revoked. Ana's independent device
      //   remains until the later password-reset test revokes it explicitly.
      $Database = new SQL(['driver' => 'sqlite', 'database' => State::$database]);
      $Operation = $Database->await($Database->query(<<<'SQL'
      SELECT
         sum(CASE WHEN users.email = 'demo@bootgly.com' THEN 1 ELSE 0 END) AS demo,
         sum(CASE WHEN users.email = 'ana@e2e.test' THEN 1 ELSE 0 END) AS ana
      FROM trusts
      INNER JOIN users ON users.id = trusts.user_id
      SQL));
      $row = $Operation->rows[0] ?? [];
      if (
         $Operation->error !== null
         || (int) ($row['demo'] ?? -1) !== 0
         || (int) ($row['ana'] ?? 0) !== 1
      ) {
         return 'aged remember replay did not isolate revocation to the affected user';
      }

      State::absorb($response);

      return true;
   }
);
