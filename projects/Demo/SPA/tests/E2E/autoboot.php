<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Demo\SPA\tests\E2E;


use const BOOTGLY_WORKING_DIR;
use const PHP_BINARY;
use const PHP_EOL;
use function array_search;
use function array_slice;
use function define;
use function defined;
use function dirname;
use function escapeshellarg;
use function exec;
use function getenv;
use function getmypid;
use function glob;
use function implode;
use function is_file;
use function is_int;
use function putenv;
use function str_starts_with;
use function sys_get_temp_dir;
use function unlink;
use RuntimeException;

use Bootgly\ACI\Logs\Data\Display;
use Bootgly\ACI\Tests;
use Bootgly\ACI\Tests\Suite;
use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Schema\Runner as Migrations;
use Bootgly\ADI\Databases\SQL\Seed\Runner as Seeds;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Configs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Configs as ServerConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Configs as ResponseConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Resources\Database as DatabaseResource;


// ? One Project per process (by design): in a full batch another E2E suite
//   has already defined BOOTGLY_PROJECT, so this suite re-runs itself in a
//   clean child process and mirrors the child's verdict.
$foreign = defined('BOOTGLY_PROJECT') && BOOTGLY_PROJECT->name !== 'SPA';

return new Suite(
   // * Config
   autoBoot: $foreign
      ? function (Suite|null $Suite = null): true {
         // ! This suite's index in the registry the runner included — the
         //   platform one lists it from its root, the project one from here
         $registry = Tests::$registry;
         // ? Only a single-registry scope can re-exec this suite
         if ($registry === '' || is_file($registry) === false) {
            throw new RuntimeException(
               'SPA API E2E needs a single-registry scope — run it with `bootgly test --web`.'
            );
         }
         $Suites = require $registry;
         $index = array_search('projects/Demo/SPA/tests/E2E/', $Suites->directories, true);
         if (is_int($index) === false) {
            $index = array_search('tests/E2E/', $Suites->directories, true);
         }
         if (is_int($index) === false) {
            throw new RuntimeException("SPA API E2E suite is not registered in {$registry}.");
         }

         // @ Re-exec in a clean process with its own BOOTGLY_PROJECT
         $bootgly = escapeshellarg(BOOTGLY_WORKING_DIR . 'bootgly');
         $suite = $index + 1;
         $scope = str_starts_with(Tests::$scope, '--') === true ? Tests::$scope . ' ' : '';
         exec("AI_AGENT=0 " . PHP_BINARY . " {$bootgly} test {$scope}{$suite} 2>&1", $output, $exit);

         // ?
         if ($exit !== 0) {
            $tail = implode(PHP_EOL, array_slice($output, -30));
            throw new RuntimeException("SPA API E2E child run failed (exit {$exit}):" . PHP_EOL . $tail);
         }

         return true;
      }
      : function (Suite|null $Suite = null): true {
         Display::show(Display::NONE);

         $root = dirname(__DIR__, 2); // projects/Demo/SPA
         $PreviousDBName = getenv('DB_NAME');

         // ! Isolated database — the specs never dirty the demo's own SQLite
         $db = sys_get_temp_dir() . '/bootgly-spa-e2e-' . getmypid() . '.sqlite';
         foreach (glob("{$db}*") ?: [] as $stale) {
            unlink($stale);
         }
         putenv("DB_NAME={$db}");

         $HTTP_Server_CLI = null;
         $started = false;
         try {
            // @ Define BOOTGLY_PROJECT — anchors views/, configs/ and builds/
            if ( !defined('BOOTGLY_PROJECT') ) {
               $Project = require "{$root}/SPA.Project.php";
               $Project->Configs = new Configs("{$root}/configs/");
               define('BOOTGLY_PROJECT', $Project);
            }

            // @ Migrate + seed the temp database
            $Database = new SQL(['driver' => 'sqlite', 'database' => $db]);
            new Migrations($Database, "{$root}/database/migrations", "{$db}.migrations.lock")->up();
            new Seeds($Database, "{$root}/database/seeders", "{$db}.seeders.lock")->run();

            HTTP_Server_CLI::pretest($Suite, specs: __DIR__);

            $HTTP_Server_CLI = new HTTP_Server_CLI(Mode: Modes::Test);
            $HTTP_Server_CLI->configure(
               new ServerConfigs(
                  host: '0.0.0.0',
                  // ? 8103 — outside 8081-8100 (core E2E), 8098 (Web App E2E)
                  //   and 8102 (Auth E2E)
                  port: 8103,
                  workers: 1
               ),
               new ResponseConfigs(
                  Resources: ['Database' => DatabaseResource::provide("{$root}/configs/")]
               )
            );

            // ! Ownership before start(): a partial fork/bind failure still
            //   drives the teardown below
            $started = true;
            $HTTP_Server_CLI->start();
            $HTTP_Server_CLI->Commands->command('test');

            return true;
         }
         finally {
            // @ Terminate the worker before its state is cleaned
            if ($started === true && $HTTP_Server_CLI !== null) {
               $HTTP_Server_CLI->Process->stopping = true;
               $HTTP_Server_CLI->Process->Children->terminate();
               $HTTP_Server_CLI->Process->State->clean();
            }

            $PreviousDBName === false
               ? putenv('DB_NAME')
               : putenv("DB_NAME={$PreviousDBName}");

            // @ Cleanup — the temp database
            foreach (glob("{$db}*") ?: [] as $file) {
               unlink($file);
            }
         }
      },
   suiteName: __NAMESPACE__,
   // * Data
   tests: $foreign
      ? [] // the child process runs (and reports) the specs
      : [
         // Reads
         '1.1-list',
         '1.2-show',
         '1.3-show-missing',
         // Create
         '2.1-create',
         '2.2-create-form',
         '2.3-create-untyped',
         '2.4-create-malformed',
         '2.5-create-list-body',
         '2.6-create-empty-title',
         '2.7-create-long-title',
         // Update
         '3.1-update-done',
         '3.2-update-title-put',
         '3.3-update-done-not-bool',
         '3.4-update-form',
         // Delete
         '4.1-delete',
         '4.2-delete-gone',
         // Unknown API paths
         '5.1-api-root',
         '5.2-api-unknown',
      ]
);
