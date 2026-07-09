<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use function getenv;

use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Schema\Runner as Migrations;
use Bootgly\ADI\Databases\SQL\Seed\Runner as Seeds;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Project;
use Web\App;


return new Project(
   // # Project Metadata
   name: 'Blog',
   description: 'Blog — Web platform MVC demo (Controllers + ORM Models + Views + Session/CSRF, SQLite)',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,

   // # Project Boot Function
   boot: function (array $arguments = [], array $options = []): void
   {
      // @ Zero-setup database — migrate + seed the SQLite file before the fork
      $file = __DIR__ . '/database/blog.sqlite';
      $Database = new SQL(['driver' => 'sqlite', 'database' => $file]);
      new Migrations($Database, __DIR__ . '/database/migrations', "{$file}.migrations.lock")->up();
      new Seeds($Database, __DIR__ . '/database/seeders', "{$file}.seeders.lock")->run();

      // @ App shell — default middleware stack (SecureHeaders, RequestId,
      //   BodyParser, CSRF); the Database resource is auto-provided from
      //   configs/database/
      $App = new App(Mode: match (true) {
         isset($options['f']) => Modes::Foreground,
         isset($options['i']) => Modes::Interactive,
         isset($options['m']) => Modes::Monitor,
         default => Modes::Daemon
      });

      $App
         ->configure(
            port: getenv('PORT') ? (int) getenv('PORT') : 8080,
            // ! Single worker — the demo SQLite file keeps writes contention-free
            workers: 1
         )
         ->load(__DIR__ . '/router')
         ->start();
   }
);
