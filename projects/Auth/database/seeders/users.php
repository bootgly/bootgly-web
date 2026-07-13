<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use function time;

use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Builder\Identifier;
use Bootgly\ADI\Databases\SQL\Seed;
use Bootgly\ADI\Databases\SQL\Seed\Seeder;
use Bootgly\API\Security\Password;


return new Seeder(
   Run: function (SQL $Database, Seed $Seed): array {
      // ! Hashed at seed time — no password hash literal lives in the repo.
      $hash = new Password()->hash('bootgly-demo');

      return [
         $Database->table(new Identifier('users'))
            ->insert()
            ->set(new Identifier('id'), 1)
            ->set(new Identifier('email'), 'demo@bootgly.com')
            ->set(new Identifier('password'), $hash)
            ->set(new Identifier('email_verified_at'), time())
            ->upsert(new Identifier('id')),
      ];
   }
);
