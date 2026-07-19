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
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\AutoTLS;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\API\Problems;
use Web\App;


return new Project(
   // # Project Metadata
   name: 'Tasks',
   description: 'Tasks API — Web platform REST demo (resources, problem+json, JWT, pagination, SQLite)',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,

   // # Project Boot Function
   boot: function (array $arguments = [], array $options = []): void
   {
      // @ Zero-setup database — migrate + seed the SQLite file before the fork
      $file = __DIR__ . '/database/tasks.sqlite';
      $Database = new SQL(['driver' => 'sqlite', 'database' => $file]);
      new Migrations($Database, __DIR__ . '/database/migrations', "{$file}.migrations.lock")->up();
      new Seeds($Database, __DIR__ . '/database/seeders', "{$file}.seeders.lock")->run();

      // @ App shell — REST stack: no CSRF (token auth), Problems as the
      //   problem+json error boundary
      $App = new App(Mode: match (true) {
         isset($options['f']) => Modes::Foreground,
         isset($options['i']) => Modes::Interactive,
         isset($options['m']) => Modes::Monitor,
         default => Modes::Daemon
      });

      $App
         ->configure(
            port: getenv('PORT') ? (int) getenv('PORT') : 8090,
            // ! Single worker — the demo SQLite file keeps writes contention-free
            workers: 1,
            // ? Auto-TLS (automatic HTTPS via Let's Encrypt) — set your domain and uncomment:
            // secure: new AutoTLS(
            //    domains: ['example.com'],
            //    email: 'admin@example.com',
            //    // staging: true, // Let's Encrypt staging CA while testing
            // ),
            middlewares: [
               new SecureHeaders,
               new RequestId,
               new BodyParser,
               new Problems
            ]
         )
         ->load(__DIR__ . '/router')
         ->start();
   }
);
