<?php

namespace Auth\tests\E2E;


use const BOOTGLY_STORAGE_DIR;
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
use function getmypid;
use function glob;
use function implode;
use function is_int;
use function putenv;
use function sys_get_temp_dir;
use function unlink;
use RuntimeException;

use Bootgly\ABI\Resources\Cache;
use Bootgly\ACI\Logs\Data\Display;
use Bootgly\ACI\Mail\Message;
use Bootgly\ACI\Tests\Suite;
use Bootgly\ADI\Databases\SQL;
use Bootgly\ADI\Databases\SQL\Schema\Runner as Migrations;
use Bootgly\ADI\Databases\SQL\Seed\Runner as Seeds;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Configs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Resources\Database as DatabaseResource;


// ? One Project per process (by design): in a full batch the Web App E2E
//   suite has already defined BOOTGLY_PROJECT with its fixtures project, so
//   this suite re-runs itself in a clean child process and mirrors the
//   child's verdict. Standalone runs (`bootgly test <index>`) take the
//   normal path below.
$foreign = defined('BOOTGLY_PROJECT') && BOOTGLY_PROJECT->name !== 'Auth';

return new Suite(
   // * Config
   autoBoot: $foreign
      ? function (Suite|null $Suite = null): true {
         // ! Resolve this suite's index from the registry (append-safe).
         $Suites = require BOOTGLY_WORKING_DIR . 'tests/autoboot.php';
         $index = array_search('projects/Auth/tests/E2E/', $Suites->directories, true);
         if (is_int($index) === false) {
            throw new RuntimeException('Auth E2E suite is not registered in tests/autoboot.php.');
         }

         // @ Re-exec in a clean process — the suite needs its own BOOTGLY_PROJECT.
         $bootgly = escapeshellarg(BOOTGLY_WORKING_DIR . 'bootgly');
         $suite = $index + 1;
         exec("AI_AGENT=0 " . PHP_BINARY . " {$bootgly} test {$suite} 2>&1", $output, $exit);

         // ?
         if ($exit !== 0) {
            $tail = implode(PHP_EOL, array_slice($output, -30));
            throw new RuntimeException("Auth E2E child run failed (exit {$exit}):" . PHP_EOL . $tail);
         }

         return true;
      }
      : function (Suite|null $Suite = null): true {
         Display::show(Display::NONE);

         // ! Rate-limit counters live in the machine-wide shared cache — clear
         //   them so previous runs (or the live demo) cannot trip the specs.
         new Cache(['driver' => 'shared', 'prefix' => 'ratelimit:'])->clear();

         $root = dirname(__DIR__, 2); // projects/Auth

         // ! Isolated database — the specs must never dirty the shipped demo sqlite
         $db = sys_get_temp_dir() . '/bootgly-auth-e2e-' . getmypid() . '.sqlite';
         foreach (glob("{$db}*") ?: [] as $stale) {
            unlink($stale);
         }
         putenv("DB_NAME={$db}");
         putenv('APP_URL=http://127.0.0.1:8102');

         // @ Define BOOTGLY_PROJECT — anchors views/, configs/ and mails/
         if ( !defined('BOOTGLY_PROJECT') ) {
            $Project = require "{$root}/Auth.project.php";
            $Project->Configs = new Configs("{$root}/configs/");
            define('BOOTGLY_PROJECT', $Project);
         }

         // @ Migrate + seed the temp database
         $Database = new SQL(['driver' => 'sqlite', 'database' => $db]);
         new Migrations($Database, "{$root}/database/migrations", "{$db}.migrations.lock")->up();
         new Seeds($Database, "{$root}/database/seeders", "{$db}.seeders.lock")->run();

         // @ Mail templates jail — the file-sink lane captures the links
         Message::$path = "{$root}/mails/";

         HTTP_Server_CLI::pretest($Suite, specs: __DIR__);

         $HTTP_Server_CLI = new HTTP_Server_CLI(Mode: Modes::Test);
         $HTTP_Server_CLI->configure(
            host: '0.0.0.0',
            // ? 8102 — outside 8081-8100 (core E2E) and 8098 (Web App E2E)
            port: 8102,
            workers: 1,
            responseResources: ['Database' => DatabaseResource::provide("{$root}/configs/")]
         );

         $HTTP_Server_CLI->start();

         $HTTP_Server_CLI->Commands->command('test');

         // @ Teardown: terminate workers and release state lock so the next
         //   suite running in the same master PHP process can bind/lock cleanly.
         $HTTP_Server_CLI->Process->stopping = true;
         $HTTP_Server_CLI->Process->Children->terminate();
         $HTTP_Server_CLI->Process->State->clean();

         // @ Cleanup — temp database + captured sink mails
         foreach (glob("{$db}*") ?: [] as $file) {
            unlink($file);
         }
         foreach (glob(BOOTGLY_STORAGE_DIR . 'mails/*.eml') ?: [] as $file) {
            unlink($file);
         }

         return true;
      },
   suiteName: __NAMESPACE__,
   // * Data
   tests: $foreign
      ? [] // the child process runs (and reports) the specs
      : [
         // Registration → verification
         '1.1-login-form',
         '1.2-register',
         '1.3-verify-link',
         '1.4-account-verified',
         '1.5-logout',
         // Login + remember-me lifecycle
         '2.1-login-refresh',
         '2.2-login-wrong',
         '2.3-login-remember',
         '2.4-remember-revival',
         '2.5-remember-replay-theft',
         // Password recovery
         '3.1-forgot-form',
         '3.2-forgot-unknown',
         '3.3-forgot-known',
         '3.4-reset-form',
         '3.5-reset-submit',
         '3.6-remember-dead-after-reset',
         '3.7-login-new-password',
         // Hardening
         '4.1-csrf-missing-token',
         '4.2-rate-limit-login',
      ]
);
