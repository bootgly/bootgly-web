<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use const Bootgly\CLI;
use Bootgly\ACI\Tests\Suite\Test;

use Demo\SPA\Frontends;


// `Frontends->build()` over a scratch project with a fake `npm` first on the
// PATH: it logs each call and writes a build, so every assertion reads which
// npm commands a start would run and what it leaves behind.
return new Test(
   description: 'Frontends: build() makes missing or stale builds, skips on purpose and warns on failure',
   skip: function_exists('proc_open') === false,
   test: function () {
      require_once __DIR__ . '/../../Frontends.php';

      // ! Scratch project: lockfile + two frontends
      $base = sys_get_temp_dir() . '/bootgly-spa-frontends-' . getmypid();
      $bin = "{$base}.bin";
      $none = "{$base}.none";
      $clean = static function () use ($base, $bin, $none): void {
         foreach ([$base, $bin, $none] as $dir) {
            if (is_dir($dir)) {
               exec('rm -rf ' . escapeshellarg($dir));
            }
         }
      };
      $clean();
      foreach (['react', 'vue'] as $name) {
         mkdir("{$base}/frontends/{$name}/src", 0755, true);
         file_put_contents("{$base}/frontends/{$name}/src/main.js", "// {$name}\n");
      }
      file_put_contents("{$base}/package-lock.json", "{}\n");
      mkdir($bin);
      mkdir($none);
      // ! The fake npm: logs its arguments; `ci` installs, `run build` builds
      //   (and fails when a `fail-<name>` file exists)
      file_put_contents("{$bin}/npm", <<<'SH'
      #!/bin/sh
      PATH=/usr/bin:/bin
      echo "$*" >> npm.log
      case "$1" in
        --version) echo 10.0.0 ;;
        ci) mkdir -p node_modules && touch node_modules/.package-lock.json ;;
        run)
          name=${3#--workspace=frontends/}
          [ -f "fail-$name" ] && exit 1
          mkdir -p "builds/$name" && echo "<!doctype html>" > "builds/$name/index.html" ;;
      esac
      exit 0
      SH);
      chmod("{$bin}/npm", 0755);

      // ! Environment and terminal — restored at the end
      $PATH = getenv('PATH');
      $SPA_BUILD = getenv('SPA_BUILD');
      $handoff = getenv('BOOTGLY_RELOAD_HANDOFF_PATH');
      putenv('SPA_BUILD');
      putenv('BOOTGLY_RELOAD_HANDOFF_PATH');
      $Output = CLI->Terminal->Output;
      $stream = $Output->stream;
      $Captured = fopen('php://memory', 'w+');
      $Output->stream = $Captured;

      // ! Helpers: the npm calls since the last read, the captured output
      $calls = static function () use ($base): array {
         $log = @file("{$base}/npm.log", FILE_IGNORE_NEW_LINES) ?: [];
         @unlink("{$base}/npm.log");

         return $log;
      };
      $said = static function () use ($Captured): string {
         rewind($Captured);
         $text = (string) stream_get_contents($Captured);
         ftruncate($Captured, 0);
         rewind($Captured);

         return $text;
      };
      // ! Every source and the install in the past, every stamp after them:
      //   a step then changes one thing by touching it now
      $settle = static function () use ($base): void {
         $Paths = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator("{$base}/frontends", FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
         );
         foreach ($Paths as $Path) {
            touch($Path->getPathname(), time() - 1000);
         }
         touch("{$base}/frontends/react", time() - 1000);
         touch("{$base}/frontends/vue", time() - 1000);
         touch("{$base}/package-lock.json", time() - 1000);
         if (is_file("{$base}/node_modules/.package-lock.json")) {
            touch("{$base}/node_modules/.package-lock.json", time() - 900);
         }
         foreach (['react', 'vue'] as $name) {
            if (is_file("{$base}/builds/{$name}/.built")) {
               touch("{$base}/builds/{$name}/.built", time() - 500);
            }
         }
      };
      $Frontends = new Frontends($base);

      try {
         // @ No npm on the PATH: warned, nothing built
         putenv("PATH={$none}");
         $result = $Frontends->build();
         yield assert(
            assertion: $result === false
               && str_contains($said(), 'npm was not found on the PATH')
               && is_dir("{$base}/builds") === false,
            description: 'without npm: returns false, warns, builds nothing'
         );

         putenv("PATH={$bin}");

         // @ Opted out and on a reload: npm is never called
         putenv('SPA_BUILD=0');
         $optedOut = $Frontends->build();
         putenv('SPA_BUILD');
         putenv('BOOTGLY_RELOAD_HANDOFF_PATH=/run/handoff');
         $reload = $Frontends->build();
         putenv('BOOTGLY_RELOAD_HANDOFF_PATH');
         yield assert(
            assertion: $optedOut === true && $reload === true && $calls() === [],
            description: 'SPA_BUILD=0 and a reload skip the build without calling npm'
         );

         // @ First start: install, then build both and stamp them
         $result = $Frontends->build();
         $said();
         yield assert(
            assertion: $result === true && $calls() === [
               '--version',
               'ci --include=dev --no-audit --no-fund',
               'run build --workspace=frontends/react',
               'run build --workspace=frontends/vue',
            ]
               && is_file("{$base}/builds/react/.built")
               && is_file("{$base}/builds/vue/.built"),
            description: 'first start: npm ci, then one build per frontend, each stamped'
         );

         // @ Nothing changed: npm is not called at all
         touch("{$base}/frontends/react/src/old.js");
         $settle();
         $result = $Frontends->build();
         yield assert(
            assertion: $result === true && $calls() === [],
            description: 'fresh builds: nothing runs'
         );

         // @ A changed source rebuilds its frontend only (no install)
         $settle();
         touch("{$base}/frontends/vue/src/main.js");
         $result = $Frontends->build();
         $said();
         yield assert(
            assertion: $result === true && $calls() === ['--version', 'run build --workspace=frontends/vue'],
            description: 'a source newer than the stamp rebuilds that frontend alone'
         );

         // @ A deleted file changes its folder's time: rebuilt too
         $settle();
         unlink("{$base}/frontends/react/src/old.js");
         $Frontends->build();
         $said();
         yield assert(
            assertion: $calls() === ['--version', 'run build --workspace=frontends/react'],
            description: 'a deleted source (its folder newer than the stamp) rebuilds'
         );

         // @ A newer lockfile reinstalls and rebuilds both
         $settle();
         touch("{$base}/package-lock.json");
         $result = $Frontends->build();
         $said();
         yield assert(
            assertion: $result === true && $calls() === [
               '--version',
               'ci --include=dev --no-audit --no-fund',
               'run build --workspace=frontends/react',
               'run build --workspace=frontends/vue',
            ],
            description: 'a lockfile newer than node_modules reinstalls and rebuilds every frontend'
         );

         // @ A missing index.html (the stamp kept) rebuilds
         $settle();
         unlink("{$base}/builds/react/index.html");
         $Frontends->build();
         $said();
         yield assert(
            assertion: $calls() === ['--version', 'run build --workspace=frontends/react'],
            description: 'a build without index.html is rebuilt'
         );

         // @ A failed build: false, reported, no stamp, the old build kept
         $settle();
         touch("{$base}/frontends/vue/src/main.js");
         touch("{$base}/fail-vue");
         $result = $Frontends->build();
         $text = $said();
         $calls();
         yield assert(
            assertion: $result === false
               && str_contains($text, 'The vue build failed')
               && is_file("{$base}/builds/vue/.built") === false
               && is_file("{$base}/builds/vue/index.html"),
            description: 'a failed build returns false, warns, leaves no stamp and keeps the previous build'
         );

         // @ The unstamped build is retried on the next start
         unlink("{$base}/fail-vue");
         $settle();
         $result = $Frontends->build();
         $said();
         yield assert(
            assertion: $result === true
               && $calls() === ['--version', 'run build --workspace=frontends/vue']
               && is_file("{$base}/builds/vue/.built"),
            description: 'the failed build is retried and stamped once it succeeds'
         );

         // @ An unreadable folder counts as changed (never a crash)
         if (function_exists('posix_geteuid') && posix_geteuid() !== 0) {
            mkdir("{$base}/frontends/react/locked");
            $settle();
            chmod("{$base}/frontends/react/locked", 0000);
            $result = $Frontends->build();
            $said();
            chmod("{$base}/frontends/react/locked", 0755);
            yield assert(
               assertion: $result === true
                  && $calls() === ['--version', 'run build --workspace=frontends/react'],
               description: 'an unreadable source folder counts as changed'
            );
         }
      }
      finally {
         $Output->stream = $stream;
         fclose($Captured);
         $PATH === false ? putenv('PATH') : putenv("PATH={$PATH}");
         $SPA_BUILD === false ? putenv('SPA_BUILD') : putenv("SPA_BUILD={$SPA_BUILD}");
         $handoff === false
            ? putenv('BOOTGLY_RELOAD_HANDOFF_PATH')
            : putenv("BOOTGLY_RELOAD_HANDOFF_PATH={$handoff}");
         $clean();
      }
   }
);
