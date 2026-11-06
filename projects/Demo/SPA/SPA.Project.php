<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */


use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Schema\Runner as Migrations;
use Bootgly\ADI\Databases\SQL\Seed\Runner as Seeds;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Project;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\API\Problems;
use Web\App;
use Web\App\Configs;

use Demo\SPA\Frontends;


return new Project(
   // # Project Metadata
   name: 'SPA',
   description: 'SPA — Web platform single-page app demo (React and Vue Vite builds over a JSON API, SQLite)',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,

   // # Project Boot Function
   boot: function (array $arguments = [], array $options = []): void
   {
      // @ The Vite builds — made before the server forks when missing or
      //   older than their sources (SPA_BUILD=0 skips; a failure only warns)
      new Frontends(__DIR__)->build();

      // @ Zero-setup database — migrate + seed the SQLite file before the fork
      $file = __DIR__ . '/database/spa.sqlite';
      $Database = new SQL(['driver' => 'sqlite', 'database' => $file]);
      new Migrations($Database, __DIR__ . '/database/migrations', "{$file}.migrations.lock")->up();
      new Seeds($Database, __DIR__ . '/database/seeders', "{$file}.seeders.lock")->run();

      $App = new App(Mode: match (true) {
         isset($options['f']) => Modes::Foreground,
         isset($options['i']) => Modes::Interactive,
         isset($options['m']) => Modes::Monitor,
         default => Modes::Daemon
      });

      $App
         ->configure(new Configs(
            port: getenv('PORT') ? (int) getenv('PORT') : 8092,
            // ! Single worker — the demo SQLite file keeps writes contention-free
            workers: 1,
            // ! JSON API without forms or cookies: it accepts only
            //   `application/json` bodies (a cross-site request then needs a
            //   CORS preflight, never granted here), so no CSRF; Problems
            //   answers its errors as problem+json
            Middlewares: [
               new SecureHeaders,
               new RequestId,
               new BodyParser,
               new Problems
            ]
         ))
         ->load(__DIR__ . '/router')
         ->start();
   }
);
