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


use const BOOTGLY_PROJECT;
use const PATHINFO_EXTENSION;
use function explode;
use function in_array;
use function pathinfo;
use function rawurldecode;
use function realpath;
use function str_contains;
use function str_starts_with;
use function strtolower;

use Bootgly\ABI\Code\__String\Path;
use Bootgly\ABI\IO\FS\File;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


/**
 * Inline file serving shared by the Web platform asset handlers (`SPA`,
 * `Statics`): the one media-type map and the one jailed read-and-send
 * routine.
 *
 * Two steps, so a handler can decide between them:
 *
 *   $file = $Files->resolve($param);           // null → refused (404)
 *   $Files->serve($file, $Response, $cache);   // false → nothing to serve
 *
 * ! Refusals: dotfile segments (`.env`, `.git/config`) — except a first
 *   `.well-known` segment (RFC 8615) —, PHP sources (`.php`, `.phtml`,
 *   `.pht`, `.phar`, `.php<N>`, `.phps`), judged on the real target of a
 *   symlink, and names the core `File` would convert to `<name>.php` /
 *   `<name>/index.php` — a public folder never hands out server-side code.
 *   Other server-side leftovers (`.inc`, `.bak`) are the deployer's call:
 *   keep them out of the public folder.
 *
 * ! Memory: `serve()` reads the whole file into memory per request — right
 *   for stylesheets, scripts, images and fonts; stream video and large
 *   downloads with `Response->upload()` instead.
 */
class Files
{
   // * Config
   /** Project directory (relative to the project root) that jails the files. */
   public private(set) string $path;

   // * Metadata
   /** Absolute jail directory, with a trailing slash. */
   public string $base {
      get => BOOTGLY_PROJECT->path . "{$this->path}/";
   }
   /**
    * Media type by lowercase file extension. An extension not listed here is
    * served as `application/octet-stream` (nosniff-safe).
    *
    * @var array<string,string>
    */
   public const array TYPES = [
      'avif' => 'image/avif',
      'css' => 'text/css; charset=UTF-8',
      'gif' => 'image/gif',
      'htm' => 'text/html; charset=UTF-8',
      'html' => 'text/html; charset=UTF-8',
      'ico' => 'image/x-icon',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'js' => 'text/javascript; charset=UTF-8',
      'json' => 'application/json',
      'map' => 'application/json',
      'mjs' => 'text/javascript; charset=UTF-8',
      'mp4' => 'video/mp4',
      'otf' => 'font/otf',
      'pdf' => 'application/pdf',
      'png' => 'image/png',
      'svg' => 'image/svg+xml',
      'ttf' => 'font/ttf',
      'txt' => 'text/plain; charset=UTF-8',
      'wasm' => 'application/wasm',
      'webm' => 'video/webm',
      'webmanifest' => 'application/manifest+json',
      'webp' => 'image/webp',
      'woff' => 'font/woff',
      'woff2' => 'font/woff2',
      'xml' => 'application/xml; charset=UTF-8'
   ];


   /**
    * @param string $path Project directory (relative to the project root) that jails the files.
    */
   public function __construct (string $path = 'statics')
   {
      // * Config
      $this->path = $path;
   }

   /**
    * Resolve a requested name — the catch-all route param exactly as the
    * router hands it, still percent-encoded — into a clean name relative to
    * the jail.
    *
    * Decodes once (`a%20b.png` → `a b.png`), normalizes separators and
    * `.`/`..` segments, and refuses an empty name, a null byte, an absolute
    * path, a name that normalizes to the jail itself (`./`, `a/../`) and any
    * dotfile segment of the normalized name — except a first `.well-known`
    * segment (RFC 8615): `.well-known/security.txt` resolves,
    * `.well-known/.env` does not.
    *
    * @param string $file The raw (percent-encoded) requested name.
    *
    * @return null|string The resolved name, or null when it is refused.
    */
   public function resolve (string $file): null|string
   {
      // ! One percent-decoding pass — the router keeps the raw request path
      $file = rawurldecode($file);

      // ? Empty, null bytes and absolute paths — before normalizing
      if ($file === '' || str_contains($file, "\0") === true || $file[0] === '/' || $file[0] === '\\') {
         return null;
      }

      // ! Separators unified, `.`/`..` collapsed
      $file = Path::normalize($file);

      // ? Nothing left (`..`, `./`, `a/../`) — the jail itself is not a file
      if ($file === '' || $file[0] === '/') {
         return null;
      }

      // ? A dotfile is never served: `.env`, `.git/config`, `.aws/credentials`
      //   — only a first `.well-known` segment is public (RFC 8615)
      foreach (explode('/', $file) as $index => $segment) {
         if (str_starts_with($segment, '.') === true && ($index > 0 || $segment !== '.well-known')) {
            return null;
         }
      }

      // :
      return $file;
   }

   /**
    * Serve one resolved name inline: jailed under `path`, typed by the
    * extension of its real target (a symlink is judged by the file it
    * points to), with the given `Cache-Control`.
    *
    * The name is not decoded again — pass what `resolve()` returned (or a
    * configured name). Nothing is written to the Response when the file is
    * a PHP source, missing, not a regular file, unreadable or outside the
    * jail. A HEAD request gets the same headers and no body (the encoder
    * drops it).
    *
    * @param string $file The resolved name, relative to the jail.
    * @param Response $Response The Response to fill.
    * @param string $cache The Cache-Control header value.
    *
    * @return false|Response The sent Response, or false when there is nothing to serve.
    */
   public function serve (string $file, Response $Response, string $cache): false|Response
   {
      // ! Jail — contained in the base, the exact file only (no `<name>.php`
      //   or `<name>/index.php` conversion)
      $base = $this->base;
      $File = new File("{$base}{$file}", base: $base);
      $File->convert = false;

      // ? Missing, not a regular file, outside the jail or unreadable
      if ($File->readable === false) {
         return false;
      }

      // ? PHP sources are server-side code, never a public file — judged on
      //   the real target, so `notes.txt -> config.php` is refused too
      $extension = strtolower(pathinfo((string) realpath($File->file), PATHINFO_EXTENSION));
      if (
         str_starts_with($extension, 'php') === true
         || in_array($extension, ['phar', 'pht', 'phtml'], true) === true
      ) {
         return false;
      }

      // @
      $contents = $File->contents;
      if ($contents === false) {
         return false;
      }

      // @ Media type by extension — unknown types stay generic (nosniff-safe)
      $Response->Header->set('Content-Type', self::TYPES[$extension] ?? 'application/octet-stream');
      $Response->Header->set('Cache-Control', $cache);

      // :
      return $Response->send($contents);
   }
}
