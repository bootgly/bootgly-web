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


use const Bootgly\WPI;
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
 *   yield $Router->route('/statics/:file*', new Statics, [GET, HEAD]);
 *
 * Files resolve inside the project `statics/` jail through `Files`
 * (percent-decoded once, path-normalized and base-contained); dotfiles
 * (but `.well-known/`), PHP sources — a symlink judged by its target — and
 * missing files answer 404; unknown extensions stay
 * `application/octet-stream`. Each file is read whole into memory.
 */
class Statics
{
   // * Config
   /** Project directory (relative to the project root) that jails the assets. */
   public string $path {
      get => $this->Files->path;
   }
   /** Route param carrying the requested file (`:file*` by convention). */
   public private(set) string $param;
   /** Cache-Control header applied to every served asset. */
   public private(set) string $cache;

   // * Metadata
   /** The jailed file server over `path`. */
   public private(set) Files $Files;


   /**
    * @param string $path Project directory (relative to the project root) that jails the assets.
    * @param string $param Route param carrying the requested file.
    * @param string $cache Cache-Control header applied to every served asset.
    */
   public function __construct (
      string $path = 'statics',
      string $param = 'file',
      string $cache = 'public, max-age=3600'
   )
   {
      // * Config
      $this->param = $param;
      $this->cache = $cache;

      // * Metadata
      $this->Files = new Files($path);
   }

   /**
    * Serve the routed asset inline, or answer 404.
    *
    * @param Request $Request
    * @param Response $Response
    */
   public function __invoke (object $Request, object $Response): object
   {
      // ! Routed file (catch-all param)
      $file = $this->Files->resolve((string) WPI->Router->Route->Params->{$this->param});

      // ? Refused: empty, null byte, absolute, the jail itself, dotfile
      if ($file === null) {
         return $Response(code: 404);
      }

      // @
      $Served = $this->Files->serve($file, $Response, $this->cache);

      // ?: Missing, unreadable or PHP source
      if ($Served === false) {
         return $Response(code: 404);
      }

      // :
      return $Served;
   }
}
