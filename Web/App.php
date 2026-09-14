<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web;


use const BOOTGLY_STORAGE_DIR;
use function defined;
use function in_array;
use function is_dir;
use Closure;
use Exception;
use Generator;
use InvalidArgumentException;

use const Bootgly\CLI;
use Bootgly\ABI\Configs as Configuring;
use Bootgly\ACI\Logs\Handlers;
use Bootgly\ACI\Logs\Handlers\File;
use Bootgly\ACI\Logs\Logger;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Workables\Server\Middleware;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Configs as ServerConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Events;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request\Configs as RequestConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Configs as ResponseConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Resources\Database as DatabaseResource;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Resources\KV as KVResource;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\BodyParser;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RequestId;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\SecureHeaders;
use Web\App\Configs;
use Web\App\Views;


/**
 * Application shell: an opinionated boot of the canonical
 * `HTTP_Server_CLI` for MVC and REST projects.
 *
 * Owns the platform defaults — global middleware stack (SecureHeaders,
 * RequestId, BodyParser, CSRF), view/layout conventions and automatic
 * Database/KV response resources when the project ships their configs —
 * while everything it wires remains plain WPI underneath.
 *
 * `configure()` takes typed Configs like every WPI node: one `App\Configs`
 * carrying the server options (with the platform defaults) and the shell
 * concerns, plus any node Configs the server takes on its own.
 */
class App
{
   // # Configs
   /** @var array<int,class-string<Configuring>> Every Configs this shell applies — its own, plus the node Configs it forwards verbatim. */
   protected const array CONFIGS = [
      Configs::class,
      RequestConfigs::class
   ];
   /** @var array<int,class-string<Configuring>> Node Configs the shell composes from `App\Configs` — refused when handed directly. */
   protected const array COMPOSED = [
      ServerConfigs::class,
      ResponseConfigs::class
   ];

   // * Config
   /**
    * Global middleware stack applied to every route.
    * `configure(new Configs(Middlewares: [...]))` replaces it wholesale.
    *
    * @var array<Middleware>
    */
   public array $Middlewares;

   // * Data
   public HTTP_Server_CLI $Server;
   public Views $Views;

   /** The App Configs last applied — a later call carrying only node Configs refines on top of it. */
   private null|Configs $Configs = null;

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
    * Configure the underlying HTTP Server with the platform defaults.
    *
    * Every concern arrives as its own Configs value object, in any order: at
    * most one `App\Configs` (none applies the platform defaults) plus any
    * node Configs the server takes on its own — `Request\Configs` is
    * forwarded verbatim. `HTTP_Server_CLI\Configs` and `Response\Configs`
    * are composed here from the `App\Configs` and refused when handed
    * directly.
    *
    * A later call carrying only node Configs refines on top of the App
    * Configs last applied — it never resets the transport; only the very
    * first call without one starts from the platform defaults.
    *
    * The Database and KV response resources are provided automatically when
    * the project ships `configs/database/` / `configs/kv/` — explicit
    * `Resources` entries win.
    *
    * The whole set is validated before anything is applied.
    *
    * @param Configuring ...$Configs `App\Configs` and/or `Request\Configs`.
    *
    * @return self The App, for chaining.
    *
    * @throws InvalidArgumentException On a repeated Configs, one this shell does not
    *                                  accept, or a node Configs it composes itself.
    */
   public function configure (Configuring ...$Configs): self
   {
      $app = static::class;

      // ? Validate — one Configs per concern, all of them applicable here.
      //   Acceptance is by exact class, and a set rejected on any ground
      //   leaves the stack, the server and every process-global static as
      //   they were.
      $AppConfigs = null;
      $Forwarded = [];
      $validated = [];
      foreach ($Configs as $Config) {
         $class = $Config::class;

         if (in_array($class, static::COMPOSED, true) === true) {
            $composer = Configs::class;

            throw new InvalidArgumentException(
               "{$app}->configure() composes {$class} itself — pass its options through {$composer}."
            );
         }
         if (in_array($class, static::CONFIGS, true) === false) {
            throw new InvalidArgumentException(
               "{$app}->configure() does not accept {$class}."
            );
         }
         if (isSet($validated[$class]) === true) {
            throw new InvalidArgumentException(
               "{$app}->configure() received two {$class} instances."
            );
         }
         $validated[$class] = true;

         if ($Config instanceof Configs) {
            $AppConfigs = $Config;
         }
         else {
            $Forwarded[] = $Config;
         }
      }

      // ! No App Configs handed in: the one last applied stays in force — a
      //   call that only forwards a node Configs refines one concern, it never
      //   resets the transport — and the platform defaults open the first call
      $Handed = $AppConfigs;
      $AppConfigs ??= $this->Configs ?? new Configs;

      // ! Response resources — project configs opt in automatically
      $Resources = $AppConfigs->Resources ?? [];
      if (defined('BOOTGLY_PROJECT') === true) {
         $configs = BOOTGLY_PROJECT->path . 'configs/';

         if (is_dir("{$configs}database") === true) {
            $Resources['Database'] ??= DatabaseResource::provide($configs);
         }
         if (is_dir("{$configs}kv") === true) {
            $Resources['KV'] ??= KVResource::provide($configs);
         }
      }

      // @ Compose the node Configs this shell owns and hand the set to the
      //   server, which validates it as a whole again before applying it
      $this->Server->configure(
         new ServerConfigs(
            host: $AppConfigs->host,
            port: $AppConfigs->port,
            workers: $AppConfigs->workers,
            secure: $AppConfigs->secure,
            user: $AppConfigs->user,
            group: $AppConfigs->group,
            AutoTLS: $AppConfigs->AutoTLS,
            enableHTTP2: $AppConfigs->enableHTTP2,
            health: $AppConfigs->health,
            maxConnections: $AppConfigs->maxConnections,
            maxConnectionsPerIP: $AppConfigs->maxConnectionsPerIP,
            connectionIdleTimeout: $AppConfigs->connectionIdleTimeout
         ),
         new ResponseConfigs(
            Resources: $Resources === [] ? null : $Resources,
            deferredTimeout: $AppConfigs->deferredTimeout
         ),
         ...$Forwarded
      );

      // @ Remembered as the set handed to the server (past its pre-start
      //   boundary the server logs the call and applies nothing — reconfigure
      //   through reload() then); the middleware stack is replaced only by an
      //   App Configs handed in THIS call — a node-only call leaves whatever
      //   the stack holds by now untouched
      $this->Configs = $AppConfigs;
      if ($Handed?->Middlewares !== null) {
         $this->Middlewares = $Handed->Middlewares;
      }

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
         ->on(Events::ServerAdvertised, function (HTTP_Server_CLI $Server): void {
            // @ Launch banner — fired on the process that owns the terminal
            //   (on Daemon mode, the launcher, so it survives the detach)
            $Output = CLI->Terminal->Output;

            $Output->render('@.;@#green:✓ Bootgly Web App started@;@.;');
            $Server->advertise();
            $Output->render('  @#green:● Ready for connections@;@..;');

            if (defined('BOOTGLY_PROJECT') === true) {
               $project = BOOTGLY_PROJECT->folder;
               $Output->render("@#Green:Tip:@; Use @#Black:`bootgly project {$project} stop`@; to stop the server.@..;");
            }
         })
         ->on(Events::ServerStopped, function (HTTP_Server_CLI $Server): void {
            $Output = CLI->Terminal->Output;

            $Output->render('@.;@#yellow:■ Bootgly Web App stopped@;@.;');
         });

      $this->Server->start();
   }
}
