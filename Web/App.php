<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web;


use const BOOTGLY_STORAGE_DIR;
use function defined;
use function is_dir;
use Closure;
use Exception;
use Generator;

use const Bootgly\CLI;
use Bootgly\ACI\Logs\Handlers;
use Bootgly\ACI\Logs\Handlers\File;
use Bootgly\ACI\Logs\Logger;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Workables\Server\Middleware;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Events;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Resources\Database as DatabaseResource;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Resources\KV as KVResource;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\App\Views;


/**
 * Application shell: an opinionated boot of the canonical
 * `HTTP_Server_CLI` for MVC and REST projects.
 *
 * Owns the platform defaults — global middleware stack (SecureHeaders,
 * RequestId, BodyParser, CSRF), view/layout conventions and automatic
 * Database/KV response resources when the project ships their configs —
 * while everything it wires remains plain WPI underneath.
 */
class App
{
   // * Config
   /**
    * Global middleware stack applied to every route.
    * `configure(middlewares:)` replaces it wholesale.
    *
    * @var array<Middleware>
    */
   public array $Middlewares;

   // * Data
   public HTTP_Server_CLI $Server;
   public Views $Views;

   // * Metadata
   private null|Closure $handler = null;


   public function __construct (Modes $Mode = Modes::Daemon)
   {
      // * Config
      $this->Middlewares = [
         new SecureHeaders,
         new RequestId,
         new BodyParser,
         new CSRF
      ];

      // * Data
      $this->Server = new HTTP_Server_CLI(Mode: $Mode);
      $this->Views = new Views;
   }

   /**
    * Configure the underlying HTTP Server with platform defaults.
    *
    * The Database and KV response resources are provided automatically when
    * the project ships `configs/database/` / `configs/kv/` — explicit
    * `$resources` entries win.
    *
    * @param string $host The host to bind.
    * @param int $port The port to bind.
    * @param int $workers The number of worker processes.
    * @param null|array<Middleware> $middlewares Replaces the default middleware stack wholesale.
    * @param null|array<string> $secure TLS context options (as in `HTTP_Server_CLI::configure`).
    * @param null|array<string,Closure> $resources Extra response resources (name => provider).
    */
   public function configure (
      string $host = '0.0.0.0',
      int $port = 8080,
      int $workers = 2,
      null|array $middlewares = null,
      null|array $secure = null,
      null|array $resources = null
   ): self
   {
      // !
      if ($middlewares !== null) {
         $this->Middlewares = $middlewares;
      }

      // ! Response resources — project configs opt in automatically
      $resources ??= [];
      if (defined('BOOTGLY_PROJECT') === true) {
         $configs = BOOTGLY_PROJECT->path . 'configs/';

         if (is_dir("{$configs}database") === true) {
            $resources['Database'] ??= DatabaseResource::provide($configs);
         }
         if (is_dir("{$configs}kv") === true) {
            $resources['KV'] ??= KVResource::provide($configs);
         }
      }

      // @
      $this->Server->configure(
         host: $host,
         port: $port,
         workers: $workers,
         secure: $secure,
         responseResources: $resources === [] ? null : $resources
      );

      // :
      return $this;
   }

   /**
    * Load the project router folder (`router.index.php` + `routes/*.php`).
    */
   public function load (string $path): self
   {
      // @
      $this->handler = HTTP_Server_CLI::$Router->load($path);

      // :
      return $this;
   }

   /**
    * Wire the platform events and start the server.
    *
    * @throws Exception When no router was loaded.
    */
   public function start (): void
   {
      // ?
      if ($this->handler === null) {
         throw new Exception('No router loaded — call App->load($path) before App->start().');
      }

      // ! Global log sink — exception reports and opted-in loggers persist to
      //   storage/logs/<channel>.log in every mode (registered before the fork
      //   so workers inherit it; essential in Daemon, where the terminal is gone)
      Logger::$Sinks ??= new Handlers;
      Logger::$Sinks->push(new File(BOOTGLY_STORAGE_DIR . 'logs/{channel}.log'));

      // !
      $handler = $this->handler;
      $Middlewares = $this->Middlewares;
      $Views = $this->Views;

      // @
      $this->Server
         ->on(Events::RequestReceived, function ($Request, $Response, $Router) use ($handler, $Middlewares, $Views): Generator {
            // @ First-request drain (once per worker): apply the view
            //   conventions onto the per-worker View resource and register
            //   the global stack after routing() resets the Router middlewares
            $Views->apply($Response);
            $Router->intercept(...$Middlewares);

            yield from $handler($Request, $Response, $Router);
         })
         ->on(Events::ServerStarted, function (HTTP_Server_CLI $Server): void {
            // @ Banner
            $Output = CLI->Terminal->Output;

            $protocol = $Server->socket ?? 'http://';
            $host = $Server->host ?? '0.0.0.0';
            $port = $Server->port ?? 0;

            $Output->render('@.;@#green:✓ Bootgly Web App started@;@.;');
            $Output->render("  Listening on @#cyan:{$protocol}{$host}:{$port}@;@.;");
            $Output->render('  @#green:● Ready for connections@;@..;');

            if (defined('BOOTGLY_PROJECT') === true) {
               $project = BOOTGLY_PROJECT->folder;
               $Output->render("@#Green:Tip:@; Use @#Black:`bootgly project stop` {$project}@; to stop the server.@..;");
            }
         })
         ->on(Events::ServerStopped, function (HTTP_Server_CLI $Server): void {
            $Output = CLI->Terminal->Output;

            $Output->render('@.;@#yellow:■ Bootgly Web App stopped@;@.;');
         });

      $this->Server->start();
   }
}
