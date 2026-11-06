<?php

namespace Web\App;

use function assert;
use function var_export;

use Bootgly\ACI\Tests\Suite\Test;

return new Test(
   description: 'Files should resolve requested names inside the jail and map media types',
   test: function () {
      // ! Files
      $Files = new Files('statics');

      // @ Requested name (raw route param) → resolved name, or null (refused)
      $cases = [
         // # Plain and percent-encoded names
         'app.css' => 'app.css',
         'assets/app-4f9a2c1e.js' => 'assets/app-4f9a2c1e.js',
         'a%20b.png' => 'a b.png',
         'img/%C3%A9t%C3%A9.png' => 'img/été.png',
         // # One decoding pass only — `%25` stays a literal percent sign
         'a%252e.css' => 'a%2e.css',
         // # Path decoding, not form decoding — `+` stays a plus sign
         'a+b.css' => 'a+b.css',
         // # Separators unified, `.`/`..` collapsed inside the jail
         'a/./b/../c.css' => 'a/c.css',
         '../app.css' => 'app.css',
         '%2e%2e/%2e%2e/app.css' => 'app.css',
         'a\\b.css' => 'a/b.css',
         // # Refused: empty, null byte, absolute
         '' => null,
         '%00' => null,
         'app.css%00.png' => null,
         '/etc/passwd' => null,
         '/../app.css' => null,
         '%2Fetc%2Fpasswd' => null,
         '\\etc\\passwd' => null,
         '..' => null,
         // # Refused: names that normalize to the jail itself
         './' => null,
         '../' => null,
         'a/../' => null,
         '%2e/' => null,
         // # Refused: dotfile segments, plain or encoded
         '.env' => null,
         '.git/config' => null,
         'a/.ssh/id_rsa' => null,
         '%2eenv' => null,
         'a%2F.htaccess' => null,
         // # `.well-known` (RFC 8615) — public as the FIRST segment only
         '.well-known/security.txt' => '.well-known/security.txt',
         '.well-known/assetlinks.json' => '.well-known/assetlinks.json',
         '.well-known/apple-app-site-association' => '.well-known/apple-app-site-association',
         '%2ewell-known/security.txt' => '.well-known/security.txt',
         '.well-known' => '.well-known',
         // # ... any dotfile below it stays refused, `..` collapses first
         '.well-known/.env' => null,
         '.well-known/a/.hidden' => null,
         '.well-known/../.env' => null,
         '.well-known/../app.css' => 'app.css',
         // # ... and only the exact, case-sensitive name is exempt
         'a/.well-known/security.txt' => null,
         '.WELL-KNOWN/security.txt' => null,
         '.well-knownx/security.txt' => null,
         '.well-know/security.txt' => null,
      ];

      foreach ($cases as $requested => $expected) {
         $resolved = $Files->resolve((string) $requested);

         yield assert(
            assertion: $resolved === $expected,
            description: "resolve('{$requested}') is " . var_export($expected, true) . ', got ' . var_export($resolved, true)
         );
      }

      // @ Media types — the one map both handlers serve with
      yield assert(
         assertion: Files::TYPES['css'] === 'text/css; charset=UTF-8'
            && Files::TYPES['js'] === 'text/javascript; charset=UTF-8'
            && Files::TYPES['html'] === 'text/html; charset=UTF-8',
         description: 'Text assets carry the UTF-8 charset'
      );
      yield assert(
         assertion: Files::TYPES['json'] === 'application/json'
            && Files::TYPES['map'] === 'application/json',
         description: 'JSON and source maps are application/json without a charset parameter (RFC 8259)'
      );
      yield assert(
         assertion: Files::TYPES['webmanifest'] === 'application/manifest+json'
            && Files::TYPES['mp4'] === 'video/mp4'
            && Files::TYPES['pdf'] === 'application/pdf',
         description: 'The map is the union of the Statics and SPA entries'
      );
      yield assert(
         assertion: isSet(Files::TYPES['php']) === false,
         description: 'PHP has no media type — it is never served'
      );

      // @ The handlers expose the jail of their one `Files` — no duplicate state
      $SPA = new SPA(path: 'spa-build');
      $Statics = new Statics(path: 'public-assets');
      yield assert(
         assertion: $SPA->path === 'spa-build' && $SPA->Files->path === 'spa-build'
            && $Statics->path === 'public-assets' && $Statics->Files->path === 'public-assets',
         description: 'SPA->path and Statics->path read the jail of their Files'
      );
   }
);
