<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\ETag;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\SPA;
use Web\App\Statics;


// ! The tags the core ETag middleware derives from each body
$tag = static fn (string $file): string => 'W/"' . hash('xxh3', (string) file_get_contents(__DIR__ . "/fixtures/{$file}")) . '"';
$index = $tag('spa/index.html');
$asset = $tag('spa/assets/app-4f9a2c1e.js');
$stylesheet = $tag('statics/app.css');

return new Test(
   description: 'SPA and Statics compose with the ETag middleware: a matching If-None-Match answers 304',

   requests: [
      // # First visits carry the tag
      static fn (): string => "GET /creative/library HTTP/1.1\r\nHost: localhost\r\n\r\n",
      static fn (): string => "GET /statics/app.css HTTP/1.1\r\nHost: localhost\r\n\r\n",
      // # Revalidations answer 304
      static fn (): string => "GET /creative/library HTTP/1.1\r\nHost: localhost\r\nIf-None-Match: {$index}\r\n\r\n",
      static fn (): string => "GET /assets/app-4f9a2c1e.js HTTP/1.1\r\nHost: localhost\r\nIf-None-Match: {$asset}\r\n\r\n",
      static fn (): string => "GET /statics/app.css HTTP/1.1\r\nHost: localhost\r\nIf-None-Match: {$stylesheet}\r\n\r\n",
      // # A stale tag gets the full body again
      static fn (): string => "GET /creative/library HTTP/1.1\r\nHost: localhost\r\nIf-None-Match: W/\"0000000000000000\"\r\n\r\n",
   ],
   response: function (Request $Request, Response $Response, Router $Router) {
      yield $Router->route('/statics/:file*', new Statics, [GET, HEAD], middlewares: [new ETag]);

      $SPA = new SPA(path: 'spa');
      yield $Router->route('/', $SPA, [GET, HEAD], middlewares: [new ETag]);
      yield $Router->route('/:file*', $SPA, [GET, HEAD], middlewares: [new ETag]);
   },

   test: function (array $responses) use ($index, $asset, $stylesheet) {
      $expectations = [
         [200, $index, 'spa-fixture-index'],
         [200, $stylesheet, 'body { color: green; }'],
         [304, $index, null],
         [304, $asset, null],
         [304, $stylesheet, null],
         [200, $index, 'spa-fixture-index'],
      ];

      foreach ($expectations as $position => [$code, $etag, $body]) {
         $response = $responses[$position] ?? '';
         $request = $position + 1;

         if (str_starts_with($response, "HTTP/1.1 {$code} ") === false) {
            return "Request #{$request}: status is not {$code}: \n{$response}";
         }
         if (str_contains($response, "ETag: {$etag}\r\n") === false) {
            return "Request #{$request}: ETag {$etag} not matched: \n{$response}";
         }
         if ($body === null && str_ends_with($response, "\r\n\r\n") === false) {
            return "Request #{$request}: a 304 must not carry a body: \n{$response}";
         }
         if ($body !== null && str_contains($response, $body) === false) {
            return "Request #{$request}: body not matched: \n{$response}";
         }
      }

      return true;
   }
);
