<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\App;


use const BOOTGLY_PROJECT;
use const PATHINFO_EXTENSION;
use function pathinfo;
use function str_contains;
use function strtolower;

use const Bootgly\WPI;
use Bootgly\ABI\Code\__String\Path;
use Bootgly\ABI\IO\FS\File;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


/**
 * Invokable static-file handler: serves project assets inline with the
 * right media type — unlike `Response->upload()`, which is download
 * semantics (`Content-Disposition: attachment` + octet-stream, rejected
 * by browsers under `nosniff` for stylesheets/scripts).
 *
 * Register it on a catch-all param route:
 *
 *   yield $Router->route('/statics/:file*', new Statics, GET);
 *
 * Files resolve inside the project `statics/` jail (path-normalized and
 * base-contained); unknown extensions stay `application/octet-stream`.
 */
class Statics
{
   // * Config
   /** Project directory (relative to the project root) that jails the assets. */
   public private(set) string $path;
   /** Route param carrying the requested file (`:file*` by convention). */
   public private(set) string $param;
   /** Cache-Control header applied to every served asset. */
   public private(set) string $cache;

   // * Metadata
   /** @var array<string,string> */
   private const array TYPES = [
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
      'webp' => 'image/webp',
      'woff' => 'font/woff',
      'woff2' => 'font/woff2',
      'xml' => 'application/xml; charset=UTF-8'
   ];


   public function __construct (
      string $path = 'statics',
      string $param = 'file',
      string $cache = 'public, max-age=3600'
   )
   {
      // * Config
      $this->path = $path;
      $this->param = $param;
      $this->cache = $cache;
   }

   /**
    * Serve the routed asset inline.
    *
    * @param Request $Request
    * @param Response $Response
    */
   public function __invoke (object $Request, object $Response): object
   {
      // ! Routed file (catch-all param)
      $file = (string) WPI->Router->Route->Params->{$this->param};

      // ? Reject empty, null bytes and absolute paths before normalizing
      if ($file === '' || str_contains($file, "\0") === true || $file[0] === '/') {
         return $Response(code: 404);
      }

      // ! Jail — normalized path, contained in the project assets base
      $base = BOOTGLY_PROJECT->path . "{$this->path}/";
      $File = new File($base . Path::normalize($file), base: $base);

      // ?
      if ($File->readable === false) {
         return $Response(code: 404);
      }
      $contents = $File->contents;
      if ($contents === false) {
         return $Response(code: 404);
      }

      // ! Media type by extension — unknown types stay generic (nosniff-safe)
      $extension = strtolower(pathinfo($File->file, PATHINFO_EXTENSION));
      $type = self::TYPES[$extension] ?? 'application/octet-stream';

      // @
      $Response->Header->set('Content-Type', $type);
      $Response->Header->set('Cache-Control', $this->cache);

      // :
      return $Response->send($contents);
   }
}
