<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use const BOOTGLY_STORAGE_DIR;
use function getenv;
use function is_string;

use Bootgly\ACI\Mail\Message;
use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Schema\Runner as Migrations;
use Bootgly\ADI\Databases\SQL\Seed\Runner as Seeds;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Project;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\AutoTLS;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication\Remember;
use Bootgly\WPI\Queues;
use Bootgly\WPI\Services\Mail;
use Web\App;


return new Project(
   // # Project Metadata
   name: 'Auth',
   description: 'Auth — session/cookie authentication scaffold (registration, e-mail verification, login/remember-me, password reset, SQLite)',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,

   // # Project Boot Function
   boot: function (array $arguments = [], array $options = []): void
   {
      $Configs = BOOTGLY_PROJECT->Configs;

      // @ Zero-setup database — migrate + seed the SQLite file before the fork
      $file = $Configs?->get('database')?->Connections->SQLite->Database->get();
      if (is_string($file) === false || $file === '') {
         $file = __DIR__ . '/database/auth.sqlite';
      }
      $Database = new SQL(['driver' => 'sqlite', 'database' => $file]);
      new Migrations($Database, __DIR__ . '/database/migrations', "{$file}.migrations.lock")->up();
      new Seeds($Database, __DIR__ . '/database/seeders', "{$file}.seeders.lock")->run();

      // @ Auth conventions — remember-cookie policy + mail templates jail,
      //   set before the fork so every worker inherits them
      $Auth = $Configs?->get('auth');
      if ($Auth !== null) {
         Remember::$name = (string) ($Auth->Remember->Name->get() ?? 'remember');
         Remember::$lifetime = (int) ($Auth->Remember->TTL->get() ?? 2592000);
      }
      Message::$path = __DIR__ . '/mails/';

      // @ SMTP — only when a host is configured (default: storage file sink)
      $Mail = $Configs?->get('mail');
      $host = $Mail !== null ? (string) ($Mail->Host->get() ?? '') : '';
      if ($host !== '') {
         Mail::boot([
            'host' => $host,
            'port' => (int) ($Mail->Port->get() ?? 587),
            'secure' => (string) ($Mail->Secure->get() ?? 'starttls'),
            'username' => (string) ($Mail->Username->get() ?? ''),
            'password' => (string) ($Mail->Password->get() ?? ''),
         ]);

         // ?: Queued lane — drained by `bootgly queue run mail`
         if ($Mail->Queue->get() === true) {
            Queues::boot(['driver' => 'file', 'path' => BOOTGLY_STORAGE_DIR . 'queues/']);
         }
      }

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
            port: getenv('PORT') ? (int) getenv('PORT') : 8087,
            // ! Single worker — the demo SQLite file keeps writes contention-free
            workers: 1,
            // ? Auto-TLS (automatic HTTPS via Let's Encrypt) — set your domain and uncomment:
            // secure: new AutoTLS(
            //    domains: ['example.com'],
            //    email: 'admin@example.com',
            //    // staging: true, // Let's Encrypt staging CA while testing
            // ),
         )
         ->load(__DIR__ . '/router')
         ->start();
   }
);
