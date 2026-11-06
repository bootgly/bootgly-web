<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\App;


use const PATHINFO_EXTENSION;
use function in_array;
use function is_file;
use function pathinfo;
use function str_starts_with;
use function strstr;

use const Bootgly\WPI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


/**
 * Invokable single-page-application handler (history-API fallback): a real
 * file of the build is served inline through `Files` (jailed, typed,
 * cached) and every other path — a client-side route — answers the index
 * document.
 *
 *   $SPA = new SPA(excludes: ['api']);
 *
 *   yield $Router->route('/', $SPA, [GET, HEAD]);
 *   yield $Router->route('/:file*', $SPA, [GET, HEAD]);
 *
 * ! The fallback is for ROUTES, never for files. A name with an extension,
 *   a name under `.well-known/` and an extension-less name that is a
 *   regular file of the build (`LICENSE`) are files: when one does not
 *   exist or is refused it answers 404, as do dotfiles, PHP sources and
 *   excluded prefixes — otherwise `/index.php`, `/.env` and `/api/missing`
 *   would all answer 200 with HTML. Any other extension-less name is a
 *   client-side route.
 */
class SPA
{
   // * Config
   /** Project directory (relative to the project root) holding the build. */
   public string $path {
      get => $this->Files->path;
   }
   /** The index document every client-side route falls back to. */
   public private(set) string $index;
   /** Route param carrying the requested path (`:file*` by convention). */
   public private(set) string $param;
   /**
    * First path segments that never receive the index — an unmatched
    * `/api/...` answers 404, not HTML. Exact, case-sensitive matches of the
    * decoded and normalized first segment: `api` excludes `/api/x` and
    * `/%61pi/x`, not `/API/x` nor `/apis/x`.
    *
    * @var array<int,string>
    */
   public private(set) array $excludes;
   /** Path prefix of content-hashed build output, cached for a year; '' disables it. */
   public private(set) string $immutable;
   /** Cache-Control of every other served file. */
   public private(set) string $cache;
   /** Body of `/robots.txt`, answered here when set; null serves the build's own file. */
   public private(set) null|string $robots;
   /**
    * Requested name → served name, for files browsers ask on their own
    * (`favicon.ico`) when the build ships another one.
    *
    * @var array<string,string>
    */
   public private(set) array $aliases;

   // * Metadata
   /** The jailed file server over `path`. */
   public private(set) Files $Files;


   /**
    * @param string $path Project directory (relative to the project root) holding the build.
    * @param string $index The index document client-side routes fall back to.
    * @param string $param Route param carrying the requested path.
    * @param array<int,string> $excludes First path segments answered 404, never the index.
    * @param string $immutable Path prefix served with a one-year immutable Cache-Control.
    * @param string $cache Cache-Control of every other served file.
    * @param null|string $robots Body of `/robots.txt`; null serves the build's own file.
    * @param array<string,string> $aliases Requested name → served name.
    */
   public function __construct (
      string $path = 'statics',
      string $index = 'index.html',
      string $param = 'file',
      array $excludes = [],
      string $immutable = 'assets/',
      string $cache = 'public, max-age=3600',
      null|string $robots = null,
      array $aliases = []
   )
   {
      // * Config
      $this->index = $index;
      $this->param = $param;
      $this->excludes = $excludes;
      $this->immutable = $immutable;
      $this->cache = $cache;
      $this->robots = $robots;
      $this->aliases = $aliases;

      // * Metadata
      $this->Files = new Files($path);
   }

   /**
    * Serve the requested file, or the index for a client-side route.
    *
    * @param Request $Request
    * @param Response $Response
    */
   public function __invoke (object $Request, object $Response): object
   {
      // ! Requested path (catch-all param; empty on the root route)
      $param = (string) WPI->Router->Route->Params->{$this->param};

      // @ A path other than the root — a file, a refusal or a route
      if ($param !== '') {
         $file = $this->Files->resolve($param);

         // ? Refused: null byte, absolute, the jail itself, dotfile
         if ($file === null) {
            return $Response(code: 404);
         }

         // ? Excluded prefixes (the API) never receive HTML
         $segment = strstr($file, '/', before_needle: true);
         $segment = $segment === false ? $file : $segment;
         if (in_array($segment, $this->excludes, true) === true) {
            return $Response(code: 404);
         }

         // ?: Crawler policy, when the application sets one
         if ($file === 'robots.txt' && $this->robots !== null) {
            $Response->Header->set('Content-Type', 'text/plain; charset=UTF-8');
            $Response->Header->set('Cache-Control', 'public, max-age=86400');

            return $Response->send($this->robots);
         }

         // @ A file — an extension, the `.well-known/` namespace or a regular
         //   file of the build — is served, or 404
         $served = $this->aliases[$file] ?? $file;
         if (
            pathinfo($served, PATHINFO_EXTENSION) !== ''
            || $segment === '.well-known'
            || is_file("{$this->Files->base}{$served}") === true
         ) {
            $cache = match (true) {
               $served === $this->index => 'no-cache',
               $this->immutable !== '' && str_starts_with($served, $this->immutable) => 'public, max-age=31536000, immutable',
               default => $this->cache
            };

            $Served = $this->Files->serve($served, $Response, $cache);

            // ?: Missing, unreadable, outside the jail or PHP source — never the index
            if ($Served === false) {
               return $Response(code: 404);
            }

            // :
            return $Served;
         }
      }

      // @ History-API fallback — the index, revalidated on every visit
      $Served = $this->Files->serve($this->index, $Response, 'no-cache');

      // ?: The build is not deployed
      if ($Served === false) {
         $Response->Header->set('Content-Type', 'text/plain; charset=UTF-8');
         $Response->Header->set('Cache-Control', 'no-store');

         return $Response(code: 503, body: 'Service Unavailable');
      }

      // :
      return $Served;
   }
}
