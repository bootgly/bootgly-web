<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Builder\Identifier;
use Bootgly\ADI\Databases\SQL\Seed;
use Bootgly\ADI\Databases\SQL\Seed\Seeder;


return new Seeder(
   Run: function (SQL $Database, Seed $Seed): array {
      return [
         $Database->table(new Identifier('posts'))
            ->insert()
            ->set(new Identifier('id'), 1, 2, 3)
            ->set(
               new Identifier('title'),
               'Hello, Bootgly Web',
               'One canonical way',
               'Zero dependencies'
            )
            ->set(
               new Identifier('body'),
               'This post was seeded by the Blog demo — a full MVC loop over the Bootgly Web platform.',
               'One HTTP server, one router, one template engine: the platform adds conventions, not competing patterns.',
               'Everything under this demo — server, ORM, migrations, sessions, CSRF — is native Bootgly code.'
            )
            ->upsert(new Identifier('id')),
      ];
   }
);
