<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\SPA;


// ! Probe => [status, needles present, needles absent]
$index = ["Content-Type: text/html; charset=UTF-8\r\n", "Cache-Control: no-cache\r\n", 'spa-fixture-index'];
$probes = [
   // # Client-side routes → the index, revalidated on every visit
   'GET /' => [200, $index, []],
   'GET /creative/library' => [200, $index, []],
   'GET /creative/library/' => [200, $index, []],
   'GET /assets' => [200, $index, []],
   // # A route named like a converted PHP file is still a route — never its source
   'GET /settings' => [200, $index, ['spa-convert-source-secret']],
   'GET /docs' => [200, $index, ['spa-docs-index-secret']],
   // # The index requested by name is never cached either
   'GET /index.html' => [200, $index, []],
   // # Content-hashed build output, cached for a year
   'GET /assets/app-4f9a2c1e.js' => [200, [
      "Content-Type: text/javascript; charset=UTF-8\r\n",
      "Cache-Control: public, max-age=31536000, immutable\r\n",
      'spa-fixture-asset-js'
   ], ['spa-fixture-index']],
   'GET /assets/app-7b3d8e05.css' => [200, [
      "Content-Type: text/css; charset=UTF-8\r\n",
      "Cache-Control: public, max-age=31536000, immutable\r\n",
      'spa-fixture-asset-css'
   ], ['spa-fixture-index']],
   // # Other build files — the configurable cache
   'GET /manifest.webmanifest' => [200, [
      "Content-Type: application/manifest+json\r\n",
      "Cache-Control: public, max-age=3600\r\n",
      'spa-fixture-manifest'
   ], []],
   // # API routes registered before the SPA keep answering
   'GET /api/users/7' => [200, ['user-7'], ['spa-fixture-index']],
   // # Percent-encoded names resolve
   'GET /a%20b.txt' => [200, ["Content-Type: text/plain; charset=UTF-8\r\n", 'spa-fixture-spaced-name'], []],
   // # An extension-less regular file of the build is a file, not a route
   'GET /LICENSE' => [200, [
      "Content-Type: application/octet-stream\r\n",
      "Cache-Control: public, max-age=3600\r\n",
      'spa-fixture-license'
   ], ['spa-fixture-index']],
   // # `.well-known` (RFC 8615) is the one public dot-directory
   'GET /.well-known/assetlinks.json' => [200, [
      "Content-Type: application/json\r\n",
      "Cache-Control: public, max-age=3600\r\n",
      '{"spa":"assetlinks"}'
   ], ['spa-fixture-index']],
   'GET /.well-known/apple-app-site-association' => [200, [
      "Content-Type: application/octet-stream\r\n",
      'spa-aasa'
   ], ['spa-fixture-index']],
   'GET /.well-known/security.txt' => [200, ["Content-Type: text/plain; charset=UTF-8\r\n", 'spa-security-txt'], ['spa-fixture-index']],
   // # A directory symlinked out of the build is still only a route
   'GET /out' => [200, $index, ['OUTSIDE_JAIL_SECRET']],
];

return new Test(
   description: 'SPA falls back to the index for client routes and serves the build files',

   requests: array_map(
      static fn (string $probe): Closure => static fn (): string => "{$probe} HTTP/1.1\r\nHost: localhost\r\n\r\n",
      array_keys($probes)
   ),
   response: function (Request $Request, Response $Response, Router $Router) {
      // @ The API first — the SPA catch-all goes last
      yield $Router->route('/api/users/:id', function (Request $Request, Response $Response) {
         return $Response(body: 'user-' . Bootgly\WPI->Router->Route->Params->id);
      }, GET);

      $SPA = new SPA(path: 'spa', excludes: ['api']);

      yield $Router->route('/', $SPA, [GET, HEAD]);
      yield $Router->route('/:file*', $SPA, [GET, HEAD]);
   },

   test: function (array $responses) use ($probes) {
      $index = 0;
      foreach ($probes as $probe => [$code, $present, $absent]) {
         $response = $responses[$index++] ?? '';

         if (str_starts_with($response, "HTTP/1.1 {$code} ") === false) {
            return "{$probe}: status is not {$code}: \n{$response}";
         }
         foreach ($present as $needle) {
            if (str_contains($response, $needle) === false) {
               return "{$probe}: `{$needle}` not matched: \n{$response}";
            }
         }
         foreach ($absent as $needle) {
            if (str_contains($response, $needle) === true) {
               return "{$probe}: `{$needle}` leaked: \n{$response}";
            }
         }
      }

      return true;
   }
);
