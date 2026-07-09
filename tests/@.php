<?php

use Bootgly\ACI\Tests\Suites;

return new Suites(
   directories: [
      // ! Web platform
      // ? Bootable + autoloader
      'Web/',
      // ? REST shell (Action, Problem/Problems, Resource, Routes)
      'Web/API/',
      // ? MVC shell (Controller/Controllers, Views, App)
      'Web/App/',
      // ? E2E (Test-mode HTTP server over the real wire)
      'Web/App/tests/E2E/',
   ]
);
