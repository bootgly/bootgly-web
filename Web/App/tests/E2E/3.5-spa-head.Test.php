<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\SPA;


return new Test(
   description: 'SPA answers HEAD with the GET headers and no body',

   requests: [
      static fn (): string => "HEAD /creative/library HTTP/1.1\r\nHost: localhost\r\n\r\n",
      static fn (): string => "HEAD /assets/app-4f9a2c1e.js HTTP/1.1\r\nHost: localhost\r\n\r\n",
   ],
   response: function (Request $Request, Response $Response, Router $Router) {
      $SPA = new SPA(path: 'spa');

      yield $Router->route('/', $SPA, [GET, HEAD]);
      yield $Router->route('/:file*', $SPA, [GET, HEAD]);
   },

   test: function (array $responses) {
      $expectations = [
         ['index.html', "Content-Type: text/html; charset=UTF-8\r\n", "Cache-Control: no-cache\r\n"],
         ['assets/app-4f9a2c1e.js', "Content-Type: text/javascript; charset=UTF-8\r\n", "Cache-Control: public, max-age=31536000, immutable\r\n"],
      ];

      foreach ($expectations as $index => [$file, $type, $cache]) {
         $response = $responses[$index] ?? '';
         $length = filesize(__DIR__ . "/fixtures/spa/{$file}");

         if (str_starts_with($response, 'HTTP/1.1 200 OK') === false) {
            return "{$file}: status is not 200 OK: \n{$response}";
         }
         foreach ([$type, $cache, "Content-Length: {$length}\r\n"] as $needle) {
            if (str_contains($response, $needle) === false) {
               return "{$file}: `{$needle}` not matched: \n{$response}";
            }
         }
         if (str_ends_with($response, "\r\n\r\n") === false) {
            return "{$file}: HEAD must not carry a body: \n{$response}";
         }
      }

      return true;
   }
);
