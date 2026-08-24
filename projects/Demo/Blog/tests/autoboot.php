<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\ACI\Tests\Suites;

// Blog test registry — this project's Suites.
//
// Each entry is a directory relative to this project's root carrying an
// `autoboot.php` that returns a Suite (an entry already inside a `tests/`
// folder loads that file directly):
//   - 'tests/project/'  → tests/project/autoboot.php
//
// Run this project's suites with `bootgly test` from the project directory
// (cd projects/Demo/Blog), one with `bootgly test <index>` and a single case with
// `bootgly test <index> <case>`.
return new Suites(
   directories: [
      // The project's own suite — the signature contract of Blog.Project.php:
      'tests/project/',
   ]
);
