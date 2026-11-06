<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\SPA;


// ! Probe => leaked needles that must stay absent (every probe answers 404, never the index)
$probes = [
   // # Excluded prefixes (the API), plain, nested and encoded
   'GET /api' => [],
   'GET /api/unknown' => [],
   'GET /api/v1/users/7' => [],
   'GET /%61pi/unknown' => [],
   'GET /./api/unknown' => [],
   // # A missing path that names a file
   'GET /assets/app-00000000.js' => [],
   'GET /favicon.png' => [],
   'GET /wp-login.php' => [],
   // # PHP sources of the build folder
   'GET /secret.php' => ['spa-php-source-secret'],
   'GET /docs/index.php' => ['spa-docs-index-secret'],
   'GET /secret.phtml' => ['spa-phtml-source-secret'],
   'GET /secret.phar' => ['spa-phar-source-secret'],
   'GET /secret.php5' => ['spa-php5-source-secret'],
   'GET /secret.pht' => ['spa-pht-source-secret'],
   // # Symlinks are judged by their real target: out of the jail, or a PHP source
   'GET /link-out.txt' => ['OUTSIDE_JAIL_SECRET'],
   'GET /out/secret.txt' => ['OUTSIDE_JAIL_SECRET'],
   'GET /link-in.txt' => ['spa-php-source-secret'],
   'GET /link-in' => ['spa-php-source-secret'],
   // # Traversal stays inside the jail — the target exists, outside it
   'GET /%2e%2e/outside/secret.txt' => ['OUTSIDE_JAIL_SECRET'],
   'GET /..%2foutside/secret.txt' => ['OUTSIDE_JAIL_SECRET'],
   // # Dotfiles, plain, nested and encoded
   'GET /.env' => ['SPA_DOTFILE_SECRET'],
   'GET /.git/config' => [],
   'GET /assets/.htaccess' => ['spa-htaccess-secret'],
   'GET /%2eenv' => ['SPA_DOTFILE_SECRET'],
   // # `.well-known/` is a file namespace: a dotfile below it, a missing
   //   name and the directory itself are 404, never the index
   'GET /.well-known/.env' => ['SPA_WELL_KNOWN_DOTFILE_SECRET'],
   'GET /.well-known/%2eenv' => ['SPA_WELL_KNOWN_DOTFILE_SECRET'],
   'GET /.well-known/change-password' => [],
   'GET /.well-known' => [],
   // # Null byte
   'GET /index.html%00.js' => [],
];

return new Test(
   description: 'SPA answers 404, never the index, to excluded prefixes, missing files, PHP sources, dotfiles and symlinks out',

   requests: array_map(
      static fn (string $probe): Closure => static fn (): string => "{$probe} HTTP/1.1\r\nHost: localhost\r\n\r\n",
      array_keys($probes)
   ),
   response: function (Request $Request, Response $Response, Router $Router) {
      $SPA = new SPA(path: 'spa', excludes: ['api']);

      yield $Router->route('/', $SPA, [GET, HEAD]);
      yield $Router->route('/:file*', $SPA, [GET, HEAD]);
   },

   test: function (array $responses) use ($probes) {
      $index = 0;
      foreach ($probes as $probe => $absent) {
         $response = $responses[$index++] ?? '';

         if (str_starts_with($response, 'HTTP/1.1 404 ') === false) {
            return "{$probe}: status is not 404: \n{$response}";
         }
         // ? A refusal carries no cache policy
         if (str_contains($response, 'Cache-Control:') === true) {
            return "{$probe}: a 404 carries Cache-Control: \n{$response}";
         }
         foreach (['spa-fixture-index', ...$absent] as $needle) {
            if (str_contains($response, $needle) === true) {
               return "{$probe}: `{$needle}` leaked: \n{$response}";
            }
         }
      }

      return true;
   }
);
