<?php

namespace Web\API;

use function assert;
use InvalidArgumentException;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should fail loud at registration time for unknown actions',
   test: function () {
      // ! Fixture controller
      $Fixture = new class {
         public function ping (object $Request, object $Response): string
         {
            return 'pong';
         }
      };

      // @ Unknown action throws at construction — never at request time
      $guarded = false;
      try {
         new Action($Fixture::class, 'pong');
      }
      catch (InvalidArgumentException) {
         $guarded = true;
      }

      yield assert(
         assertion: $guarded === true,
         description: 'Constructing an Action with an unknown method throws'
      );

      // @ Unknown class throws too (method_exists on a missing class is false)
      $guarded = false;
      try {
         // @phpstan-ignore argument.type
         new Action('Web\API\ThisControllerDoesNotExist', 'ping');
      }
      catch (InvalidArgumentException) {
         $guarded = true;
      }

      yield assert(
         assertion: $guarded === true,
         description: 'Constructing an Action with an unknown class throws'
      );
   }
);
