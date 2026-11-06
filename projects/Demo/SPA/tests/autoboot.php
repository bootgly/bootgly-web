<?php

use Bootgly\ACI\Tests\Suites;

// SPA test registry — this project's Suites.
//
// Each entry is a directory relative to this project's root carrying an
// `autoboot.php` that returns a Suite (an entry already inside a `tests/`
// folder loads that file directly):
//   - 'tests/project/'  → tests/project/autoboot.php
//   - 'tests/E2E/'      → tests/E2E/autoboot.php
//
// Run this project's suites with `bootgly test` from the project directory
// (cd projects/Demo/SPA), one with `bootgly test <index>` and a single case with
// `bootgly test <index> <case>`.
return new Suites(
   directories: [
      // The project's own suite — the signature contract of SPA.Project.php:
      'tests/project/',
      // The JSON API over the real wire (Test-mode server on port 8103):
      'tests/E2E/',
   ]
);
