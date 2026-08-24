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
      // ? Auth demo E2E (session/cookie flows over the real wire)
      'projects/Demo/Auth/tests/E2E/',
      // ! Web projects — example signature suites (kit import guide)
      'projects/Demo/Blog/tests/project/',
      'projects/Demo/Chat/tests/project/',
      'projects/Demo/Site/tests/project/',
      'projects/Demo/Tasks/tests/project/',
   ]
);
