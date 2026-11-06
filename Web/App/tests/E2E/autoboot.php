<?php

namespace Web\App\tests\E2E;


use function define;
use function defined;
use function is_link;
use function symlink;
use function unlink;
use RuntimeException;

use Bootgly\ACI\Logs\Data\Display;
use Bootgly\ACI\Tests\Suite;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Configs;


return new Suite(
   // * Config
   autoBoot: function (Suite|null $Suite = null): true {
      Display::show(Display::NONE);

      // @ Define BOOTGLY_PROJECT — the fixture project anchors views/ for
      //   render specs and the error-page lookups of the core Catcher
      if ( !defined('BOOTGLY_PROJECT') ) {
         $TestProject = require __DIR__ . '/fixtures/fixtures.Project.php';
         define('BOOTGLY_PROJECT', $TestProject);
      }

      HTTP_Server_CLI::pretest($Suite, specs: __DIR__);

      // ! Symlink fixtures (link => target, relative to the link) — made at
      //   run time, never committed: out of the jail to a non-PHP file, a
      //   directory out of the jail, and in-jail names of PHP sources
      $links = [
         'statics/link-out.txt' => '../outside/secret.txt',
         'statics/out' => '../outside',
         'statics/link-in.txt' => 'config.php',
         'spa/link-out.txt' => '../outside/secret.txt',
         'spa/out' => '../outside',
         'spa/link-in.txt' => 'secret.php',
         'spa/link-in' => 'secret.php',
      ];
      $unlink = static function () use ($links): void {
         foreach ($links as $link => $target) {
            if (is_link(__DIR__ . "/fixtures/{$link}") === true) {
               unlink(__DIR__ . "/fixtures/{$link}");
            }
         }
      };

      // @ Stale links of an interrupted run go first
      $unlink();
      foreach ($links as $link => $target) {
         if (symlink($target, __DIR__ . "/fixtures/{$link}") === false) {
            $unlink();

            throw new RuntimeException("E2E fixture symlink `{$link}` could not be created.");
         }
      }

      try {
         $HTTP_Server_CLI = new HTTP_Server_CLI(Mode: Modes::Test);
         $HTTP_Server_CLI->configure(
            new Configs(
               host: '0.0.0.0',
               // ? 8098 — outside the 8081-8097 range claimed by the core E2E suites
               port: 8098,
               workers: 1
            )
         );

         $HTTP_Server_CLI->start();

         $HTTP_Server_CLI->Commands->command('test');

         // @ Teardown: terminate workers and release state lock so the next
         //   suite running in the same master PHP process can bind/lock cleanly.
         $HTTP_Server_CLI->Process->stopping = true;
         $HTTP_Server_CLI->Process->Children->terminate();
         $HTTP_Server_CLI->Process->State->clean();
      }
      finally {
         // @ Teardown: the symlink fixtures go, passed or failed
         $unlink();
      }

      return true;
   },
   suiteName: __NAMESPACE__,
   // * Data
   tests: [
      // MVC shell over the wire
      '1.1-controllers-map-list',
      '1.2-controllers-map-show-param',
      '1.3-controller-render-view',
      '1.4-statics-inline-css',
      '1.5-statics-refuses-probes',
      '1.6-statics-head',
      // problem+json error boundary
      '2.1-problem-thrown-422',
      '2.2-problem-production-500',
      // problem+json error boundary — deferred work (BG-14)
      '2.3-problem-deferred-422',
      '2.4-problem-deferred-production-500',
      '2.5-problem-deferred-test-catcher',
      // single-page application (history-API fallback) + Files
      '3.1-spa-routes-and-files',
      '3.2-spa-refuses-probes',
      '3.3-spa-robots-aliases',
      '3.4-spa-not-deployed-503',
      '3.5-spa-head',
      '3.6-spa-statics-etag-304',
   ]
);
