<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Demo\SPA;


use const STDERR;
use const STDOUT;
use function filemtime;
use function function_exists;
use function getenv;
use function is_file;
use function max;
use function posix_geteuid;
use function proc_close;
use function proc_open;
use function touch;
use function unlink;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

use const Bootgly\CLI;


/**
 * The Vite frontends of this demo, built on start when needed.
 *
 * A frontend is built when its build has no stamp (`builds/<name>/.built`,
 * written once a build succeeds — a dotfile, never served) or the stamp is
 * older than any of its sources: the files and folders of `frontends/<name>/`
 * (a folder's time catches a deleted or renamed file) and the workspace
 * lockfile. Dependencies are installed with `npm ci --include=dev` when
 * `node_modules/` is missing or older than the lockfile.
 *
 * Skipped with a notice: `SPA_BUILD=0`, a root launch (npm would run its
 * lifecycle scripts as root and leave root-owned files) and a reload — the
 * re-executed master boots again while its workers are drained, so a build
 * there would lengthen the outage, and a daemon's output goes nowhere.
 *
 * A failure — no `npm` on the PATH, an unreadable source tree, a failed
 * install or build — is reported and never stops the start: the landing page
 * and the API serve on, and a frontend without a build answers `503`.
 */
class Frontends
{
   // * Config
   /** Absolute project directory (holds `package.json`, `frontends/` and `builds/`). */
   public private(set) string $base;
   /**
    * Frontend names — directories under `frontends/`, builds under `builds/`.
    *
    * @var list<string>
    */
   public private(set) array $names;


   /**
    * @param string $base Absolute project directory.
    * @param list<string> $names Frontend names.
    */
   public function __construct (string $base, array $names = ['react', 'vue'])
   {
      // * Config
      $this->base = $base;
      $this->names = $names;
   }

   /**
    * Build every frontend whose build is missing or stale.
    *
    * @return bool True when every frontend has a fresh build or the build is
    *    skipped on purpose (`SPA_BUILD=0`, a reload); false when one could
    *    not be built (or a root launch skipped a needed build).
    */
   public function build (): bool
   {
      // ? Opted out
      if (getenv('SPA_BUILD') === '0') {
         return true;
      }
      // ? A reload: the fresh master boots while the workers are drained —
      //   the builds stay as they are (`stop` + `start` rebuilds). The core
      //   sets this variable for the re-executed master only.
      if (getenv('BOOTGLY_RELOAD_HANDOFF_PATH') !== false) {
         return true;
      }

      $Output = CLI->Terminal->Output;

      // ! Frontends to build
      $stale = [];
      foreach ($this->names as $name) {
         if ($this->check($name) === true) {
            $stale[] = $name;
         }
      }

      // ? Every build is fresh
      if ($stale === []) {
         return true;
      }
      // ? Root launch — npm never runs as root here
      if (function_exists('posix_geteuid') === true && posix_geteuid() === 0) {
         $Output->render('@.;@#yellow:⚠ Launched as root — the SPA builds are not made (npm would run as root).@;@.;');
         $Output->render('@#yellow:  Build them as your user: `npm install && npm run build` in this project.@;@.;');

         return false;
      }
      // ? No npm to build with
      if ($this->run(['npm', '--version'], quiet: true) !== 0) {
         $Output->render('@.;@#yellow:⚠ npm was not found on the PATH — the SPA builds are not made.@;@.;');
         $Output->render('@#yellow:  Install Node.js 22.22 or newer, then run `npm install && npm run build` in this project.@;@.;');

         return false;
      }

      // @ Dependencies — missing, or older than the lockfile
      $lock = (int) @filemtime("{$this->base}/package-lock.json");
      $installed = (int) @filemtime("{$this->base}/node_modules/.package-lock.json");
      if ($installed === 0 || $installed < $lock) {
         $Output->render('@.;@#cyan:↓ Installing the frontend dependencies (npm ci)…@;@.;');

         if ($this->run(['npm', 'ci', '--include=dev', '--no-audit', '--no-fund']) !== 0) {
            $Output->render('@.;@#red:✗ npm ci failed — the SPA builds are not made.@;@.;');

            return false;
         }
      }

      // @@ Builds
      $built = true;
      foreach ($stale as $name) {
         $Output->render("@.;@#cyan:⚙ Building the {$name} frontend (vite build)…@;@.;");

         // ! The stamp goes first: a build cut short leaves none behind
         @unlink("{$this->base}/builds/{$name}/.built");

         if ($this->run(['npm', 'run', 'build', "--workspace=frontends/{$name}"]) !== 0) {
            $Output->render("@.;@#red:✗ The {$name} build failed — /{$name}/ serves its previous build, or 503.@;@.;");
            $built = false;

            continue;
         }

         // @ Stamp the finished build
         if (@touch("{$this->base}/builds/{$name}/.built") === false) {
            $Output->render("@.;@#yellow:⚠ The {$name} build could not be stamped — the next start builds it again.@;@.;");
         }
      }

      // :
      return $built;
   }

   /**
    * Check whether a frontend needs a build: it has no stamp, or the stamp is
    * older than its newest source. A tree that cannot be read counts as
    * changed — a build then reports what is wrong with it.
    */
   private function check (string $name): bool
   {
      $stamp = (int) @filemtime("{$this->base}/builds/{$name}/.built");

      // ? Never built (or a build cut short)
      if ($stamp === 0 || is_file("{$this->base}/builds/{$name}/index.html") === false) {
         return true;
      }

      // ! Newest source: the lockfile, the tree and every file and folder in
      //   it, without dependencies; a link that leads nowhere is skipped
      $newest = max(
         (int) @filemtime("{$this->base}/package-lock.json"),
         (int) @filemtime("{$this->base}/frontends/{$name}")
      );

      try {
         $Files = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
               new RecursiveDirectoryIterator("{$this->base}/frontends/{$name}", FilesystemIterator::SKIP_DOTS),
               static function (SplFileInfo $File): bool {
                  return $File->getFilename() !== 'node_modules';
               }
            ),
            RecursiveIteratorIterator::SELF_FIRST
         );
         /** @var SplFileInfo $File */
         foreach ($Files as $File) {
            $newest = max($newest, (int) @filemtime($File->getPathname()));
         }
      }
      catch (Throwable) {
         return true;
      }

      // :
      return $stamp < $newest;
   }

   /**
    * Run a command in the project directory, its output on this terminal.
    *
    * @param list<string> $command
    *
    * @return int The exit code (127 when it cannot be started).
    */
   private function run (array $command, bool $quiet = false): int
   {
      $Process = @proc_open(
         $command,
         [
            0 => ['file', '/dev/null', 'r'],
            1 => $quiet ? ['file', '/dev/null', 'w'] : STDOUT,
            2 => $quiet ? ['file', '/dev/null', 'w'] : STDERR,
         ],
         $pipes,
         $this->base
      );

      // ? Not started
      if ($Process === false) {
         return 127;
      }

      // :
      return proc_close($Process);
   }
}
