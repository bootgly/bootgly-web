<?php

namespace Web\App\tests\E2E;


use function define;
use function defined;

use Bootgly\ACI\Logs\Data\Display;
use Bootgly\ACI\Tests\Suite;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;


return new Suite(
   // * Config
   autoBoot: function (Suite|null $Suite = null): true {
      Display::show(Display::NONE);

      // @ Define BOOTGLY_PROJECT — the fixture project anchors views/ for
      //   render specs and the error-page lookups of the core Catcher
      if ( !defined('BOOTGLY_PROJECT') ) {
         $TestProject = require __DIR__ . '/fixtures/fixtures.project.php';
         define('BOOTGLY_PROJECT', $TestProject);
      }

      HTTP_Server_CLI::pretest($Suite, specs: __DIR__);

      $HTTP_Server_CLI = new HTTP_Server_CLI(Mode: Modes::Test);
      $HTTP_Server_CLI->configure(
         host: '0.0.0.0',
         // ? 8098 — outside the 8081-8097 range claimed by the core E2E suites
         port: 8098,
         workers: 1
      );

      $HTTP_Server_CLI->start();

      $HTTP_Server_CLI->Commands->command('test');

      // @ Teardown: terminate workers and release state lock so the next
      //   suite running in the same master PHP process can bind/lock cleanly.
      $HTTP_Server_CLI->Process->stopping = true;
      $HTTP_Server_CLI->Process->Children->terminate();
      $HTTP_Server_CLI->Process->State->clean();

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
      // problem+json error boundary
      '2.1-problem-thrown-422',
      '2.2-problem-production-500',
   ]
);
