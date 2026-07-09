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
         $Database->table(new Identifier('tasks'))
            ->insert()
            ->set(new Identifier('id'), 1, 2, 3)
            ->set(
               new Identifier('title'),
               'Read the Web platform guide',
               'Build a REST resource',
               'Ship it'
            )
            ->set(new Identifier('done'), 1, 0, 0)
            ->upsert(new Identifier('id')),
      ];
   }
);
