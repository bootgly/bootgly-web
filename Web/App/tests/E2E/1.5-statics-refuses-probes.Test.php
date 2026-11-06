<?php


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Web\App\Statics;


// ! Probe => [status, needles present, needles absent]
$probes = [
   // # Controls — the jail still serves its files
   'GET /statics/app.css' => [200, ["Content-Type: text/css; charset=UTF-8\r\n", 'body { color: green; }'], []],
   'GET /statics/data.json' => [200, ["Content-Type: application/json\r\n", '{"statics":"json"}'], []],
   // # Percent-encoded names resolve
   'GET /statics/a%20b.txt' => [200, ["Content-Type: text/plain; charset=UTF-8\r\n", 'statics-spaced-name'], []],
   // # `.well-known` (RFC 8615) is the one public dot-directory
   'GET /statics/.well-known/assetlinks.json' => [200, ["Content-Type: application/json\r\n", '{"statics":"assetlinks"}'], []],
   'GET /statics/.well-known/apple-app-site-association' => [200, ["Content-Type: application/octet-stream\r\n", 'statics-aasa'], []],
   'GET /statics/.well-known/security.txt' => [200, ["Content-Type: text/plain; charset=UTF-8\r\n", 'statics-security-txt'], []],
   'GET /statics/%2ewell-known/security.txt' => [200, ['statics-security-txt'], []],
   // # ... a dotfile below it is not, nor is the directory itself
   'GET /statics/.well-known/.env' => [404, [], ['STATICS_WELL_KNOWN_DOTFILE_SECRET']],
   'GET /statics/.well-known/%2eenv' => [404, [], ['STATICS_WELL_KNOWN_DOTFILE_SECRET']],
   'GET /statics/.well-known/..%2f.env' => [404, [], ['STATICS_DOTFILE_SECRET']],
   'GET /statics/.well-known' => [404, [], []],
   // # PHP sources are never served
   'GET /statics/config.php' => [404, [], ['statics-php-source-secret']],
   'GET /statics/Upper.PHP' => [404, [], ['statics-upper-php-secret']],
   'GET /statics/secret.phtml' => [404, [], ['statics-phtml-source-secret']],
   'GET /statics/secret.phar' => [404, [], ['statics-phar-source-secret']],
   'GET /statics/secret.php5' => [404, [], ['statics-php5-source-secret']],
   'GET /statics/secret.pht' => [404, [], ['statics-pht-source-secret']],
   // # Symlinks are judged by their real target: out of the jail, or a PHP source
   'GET /statics/link-out.txt' => [404, [], ['OUTSIDE_JAIL_SECRET']],
   'GET /statics/out/secret.txt' => [404, [], ['OUTSIDE_JAIL_SECRET']],
   'GET /statics/link-in.txt' => [404, [], ['statics-php-source-secret']],
   // # Names the core File would convert to `<name>.php` / `<name>/index.php`
   'GET /statics/config' => [404, [], ['statics-php-source-secret']],
   'GET /statics/docs' => [404, [], ['statics-docs-index-secret']],
   'GET /statics/docs/' => [404, [], ['statics-docs-index-secret']],
   // # Dotfiles, plain and encoded
   'GET /statics/.env' => [404, [], ['STATICS_DOTFILE_SECRET']],
   'GET /statics/%2eenv' => [404, [], ['STATICS_DOTFILE_SECRET']],
   // # Traversal stays inside the jail — the target exists, outside it
   'GET /statics/%2e%2e/outside/secret.txt' => [404, [], ['OUTSIDE_JAIL_SECRET']],
   'GET /statics/..%2foutside/secret.txt' => [404, [], ['OUTSIDE_JAIL_SECRET']],
   'GET /statics/a/%2e%2e/%2e%2e/outside/secret.txt' => [404, [], ['OUTSIDE_JAIL_SECRET']],
   // # Missing file
   'GET /statics/missing.css' => [404, [], []],
];

return new Test(
   description: 'Statics refuses PHP sources, converted names, dotfiles and symlinks out, and serves .well-known',

   requests: array_map(
      static fn (string $probe): Closure => static fn (): string => "{$probe} HTTP/1.1\r\nHost: localhost\r\n\r\n",
      array_keys($probes)
   ),
   response: function (Request $Request, Response $Response, Router $Router) {
      yield $Router->route('/statics/:file*', new Statics, [GET, HEAD]);
   },

   test: function (array $responses) use ($probes) {
      $index = 0;
      foreach ($probes as $probe => [$code, $present, $absent]) {
         $response = $responses[$index++] ?? '';

         if (str_starts_with($response, "HTTP/1.1 {$code} ") === false) {
            return "{$probe}: status is not {$code}: \n{$response}";
         }
         // ? A refusal carries no cache policy
         if ($code === 404 && str_contains($response, 'Cache-Control:') === true) {
            return "{$probe}: a 404 carries Cache-Control: \n{$response}";
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
