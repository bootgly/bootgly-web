<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\SPA;


// ! Probe => [needles present, needles absent] (every probe answers 200)
$probes = [
   // # robots set — answered from the configuration, not the build
   'GET /robots.txt' => [[
      "Content-Type: text/plain; charset=UTF-8\r\n",
      "Cache-Control: public, max-age=86400\r\n",
      "User-agent: *\nDisallow: /\n"
   ], ['spa-fixture-robots']],
   // # robots unset — the build's own file
   'GET /built/robots.txt' => [[
      "Content-Type: text/plain; charset=UTF-8\r\n",
      'spa-fixture-robots'
   ], ['Disallow']],
   // # Alias — the name browsers ask on their own, served from the shipped file
   'GET /favicon.ico' => [[
      "Content-Type: image/x-icon\r\n",
      "Cache-Control: public, max-age=3600\r\n",
      'spa-fixture-logo-ico'
   ], ['spa-fixture-index']],
];

return new Test(
   description: 'SPA answers robots.txt from its configuration and serves aliased names',

   requests: array_map(
      static fn (string $probe): Closure => static fn (): string => "{$probe} HTTP/1.1\r\nHost: localhost\r\n\r\n",
      array_keys($probes)
   ),
   response: function (Request $Request, Response $Response, Router $Router) {
      yield $Router->route('/built/:file*', new SPA(path: 'spa'), [GET, HEAD]);
      yield $Router->route('/:file*', new SPA(
         path: 'spa',
         robots: "User-agent: *\nDisallow: /\n",
         aliases: ['favicon.ico' => 'logo.ico']
      ), [GET, HEAD]);
   },

   test: function (array $responses) use ($probes) {
      $index = 0;
      foreach ($probes as $probe => [$present, $absent]) {
         $response = $responses[$index++] ?? '';

         if (str_starts_with($response, 'HTTP/1.1 200 ') === false) {
            return "{$probe}: status is not 200: \n{$response}";
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
