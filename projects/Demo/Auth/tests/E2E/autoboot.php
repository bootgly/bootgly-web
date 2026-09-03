<?php

namespace Demo\Auth\tests\E2E;


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
use function getenv;
use function getmypid;
use function glob;
use function implode;
use function is_file;
use function is_int;
use function putenv;
use function random_int;
use function str_starts_with;
use function sys_get_temp_dir;
use function unlink;
use RuntimeException;

use Bootgly\ABI\Resources\Cache;
use Bootgly\ABI\Resources\Cache\Drivers\Shared;
use Bootgly\ACI\Logs\Data\Display;
use Bootgly\ACI\Mail\Message;
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
         // ! Resolve this suite's index from the registry it BELONGS to —
         //   the runner records the file it included and the scope selector
         //   that picks it (from a kit: `Web/tests/autoboot.php` + `--web`).
         //   Guessing it from the working directory would read the USER's
         //   registry from a kit, which never lists a platform path (BG-9) —
         //   and a merged `projects/` run records no single registry at all.
         $registry = Tests::$registry;
         // ? Only a single-registry scope can re-exec this suite
         if ($registry === '' || is_file($registry) === false) {
            throw new RuntimeException(
               'Auth E2E needs a single-registry scope — run it with `bootgly test --web`.'
            );
         }
         $Suites = require $registry;
         $index = array_search('projects/Demo/Auth/tests/E2E/', $Suites->directories, true);
         if (is_int($index) === false) {
            throw new RuntimeException("Auth E2E suite is not registered in {$registry}.");
         }

         // @ Re-exec in a clean process — the suite needs its own
         //   BOOTGLY_PROJECT. The recorded scope flag re-selects the same
         //   registry; a flagless scope re-execs exactly as before.
         $bootgly = escapeshellarg(BOOTGLY_WORKING_DIR . 'bootgly');
         $suite = $index + 1;
         $scope = str_starts_with(Tests::$scope, '--') === true ? Tests::$scope . ' ' : '';
         exec("AI_AGENT=0 " . PHP_BINARY . " {$bootgly} test {$scope}{$suite} 2>&1", $output, $exit);

         // ?
         if ($exit !== 0) {
            $tail = implode(PHP_EOL, array_slice($output, -30));
            throw new RuntimeException("Auth E2E child run failed (exit {$exit}):" . PHP_EOL . $tail);
         }

         return true;
      }
      : function (Suite|null $Suite = null): true {
         Display::show(Display::NONE);

         $root = dirname(__DIR__, 2); // projects/Demo/Auth
         $PreviousDBName = getenv('DB_NAME');
         $PreviousAppURL = getenv('APP_URL');
         $PreviousRateSegment = getenv('AUTH_E2E_RATE_LIMIT_SEGMENT');

         // ! Isolated database — the specs must never dirty the shipped demo sqlite
         $db = sys_get_temp_dir() . '/bootgly-auth-e2e-' . getmypid() . '.sqlite';
         foreach (glob("{$db}*") ?: [] as $stale) {
            unlink($stale);
         }
         putenv("DB_NAME={$db}");
         putenv('APP_URL=http://127.0.0.1:8102');

         $RateLimitCache = null;
         $HTTP_Server_CLI = null;
         $started = false;
         try {
            // ! A private segment isolates rate-limit counters from the live
            //   demo and concurrent tests. The encompassing finally owns it
            //   even when migration, seed or pretest setup fails.
            $segment = random_int(10_000_000, 2_000_000_000);
            putenv("AUTH_E2E_RATE_LIMIT_SEGMENT={$segment}");
            $RateLimitCache = new Cache([
               'driver' => 'shared',
               'prefix' => 'ratelimit:',
               'segment' => $segment,
            ]);
            $RateLimitCache->clear();

            require_once __DIR__ . '/fixtures/State.php';
            fixtures\State::$database = $db;
            fixtures\State::$segment = $segment;

            // @ Define BOOTGLY_PROJECT — anchors views/, configs/ and mails/
            if ( !defined('BOOTGLY_PROJECT') ) {
               $Project = require "{$root}/Auth.Project.php";
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
               new ServerConfigs(
                  host: '0.0.0.0',
                  // ? 8102 — outside 8081-8100 (core E2E) and 8098 (Web App E2E)
                  port: 8102,
                  workers: 1
               ),
               new ResponseConfigs(
                  Resources: ['Database' => DatabaseResource::provide("{$root}/configs/")]
               )
            );

            // ! Mark ownership before start(): a partial fork/bind failure must
            //   still drive the bounded process/state teardown below.
            $started = true;
            $HTTP_Server_CLI->start();
            $HTTP_Server_CLI->Commands->command('test');

            return true;
         }
         finally {
            // @ Terminate workers before removing their shared-memory segment.
            if ($started === true && $HTTP_Server_CLI !== null) {
               $HTTP_Server_CLI->Process->stopping = true;
               $HTTP_Server_CLI->Process->Children->terminate();
               $HTTP_Server_CLI->Process->State->clean();
            }

            if ($RateLimitCache !== null) {
               $Driver = $RateLimitCache->Driver;
               if ($Driver instanceof Shared) {
                  $Driver->destroy();
               }
            }
            $PreviousRateSegment === false
               ? putenv('AUTH_E2E_RATE_LIMIT_SEGMENT')
               : putenv("AUTH_E2E_RATE_LIMIT_SEGMENT={$PreviousRateSegment}");
            $PreviousDBName === false
               ? putenv('DB_NAME')
               : putenv("DB_NAME={$PreviousDBName}");
            $PreviousAppURL === false
               ? putenv('APP_URL')
               : putenv("APP_URL={$PreviousAppURL}");

            // @ Cleanup — temp database + captured sink mails.
            foreach (glob("{$db}*") ?: [] as $file) {
               unlink($file);
            }
            foreach (glob(BOOTGLY_STORAGE_DIR . 'mails/*.eml') ?: [] as $file) {
               unlink($file);
            }
         }
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
         '2.5-remember-duplicate-grace',
         '2.6-remember-aged-replay-theft',
         // Password recovery
         '3.1-forgot-form',
         '3.2-forgot-unknown',
         '3.3-forgot-known',
         '3.4-reset-form',
         '3.5-reset-submit',
         '3.6-remember-dead-after-reset',
         // Hardening — run before the final successful login regenerates the
         // session id and invalidates the fixture's currently masked CSRF token.
         '4.1-csrf-missing-token',
         '4.2-rate-limit-login',
         '3.7-login-new-password',
      ]
);
