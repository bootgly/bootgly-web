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


use function is_string;
use Closure;
use InvalidArgumentException;

use Bootgly\ABI\Argument;
use Bootgly\API\Workables\Server\Middleware;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\AutoTLS;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Configs as ServerConfigs;


/**
 * Configuration of a Web App.
 *
 * Handed to `App->configure()` — at most one per call; none applies the
 * platform defaults. It carries every option of the underlying
 * `HTTP_Server_CLI\Configs` with the Web platform defaults filled in
 * (`host` `'0.0.0.0'`, `port` `8080`, `workers` `2`, `health` `'/health'`)
 * plus the three shell concerns: the global middleware stack, the extra
 * response resources and the deferred-response budget.
 *
 * Named arguments only: `$Named` is a guard slot no named call ever fills, so
 * a positional `new Configs('0.0.0.0', 8080, 2)` is rejected by the engine
 * (and flagged by static analysis) before it can silently bind the wrong
 * values.
 */
class Configs extends ServerConfigs
{
   // * Config
   /**
    * Global middleware stack — replaces the App default wholesale; `null` keeps it.
    *
    * @var null|array<int,Middleware>
    */
   public private(set) null|array $Middlewares;
   /**
    * Extra response resources (name => provider) — an explicit entry wins over
    * the Database/KV resource the App provides from the project configs.
    *
    * @var null|array<string,Closure>
    */
   public private(set) null|array $Resources;
   /** Seconds a deferred response may take before it is cancelled; `null` keeps the server default. */
   public private(set) null|int|float $deferredTimeout;


   /**
    * @param Argument $Named Guard slot — never pass it; it only rejects positional calls.
    * @param null|string $host Bind address (`'0.0.0.0'` by default).
    * @param null|int $port Listen port (`8080` by default).
    * @param null|int $workers Worker processes to fork (`2` by default).
    * @param null|array<string,mixed> $secure Secure SSL/TLS Stream Context options.
    * @param null|string $user User to drop privileges to after socket binding.
    * @param null|string $group Group to drop privileges to after socket binding.
    * @param null|AutoTLS $AutoTLS Auto-TLS runtime; mutually exclusive with `secure`.
    * @param null|bool $enableHTTP2 `false` serves HTTP/1.x only.
    * @param null|string $health Health endpoint path (`'/health'` by default); `null` disables it.
    * @param null|int $maxConnections Maximum established connections per worker.
    * @param null|int $maxConnectionsPerIP Maximum established connections per client IP.
    * @param null|int $connectionIdleTimeout Seconds of transport silence before a connection is closed.
    * @param null|array<array-key,mixed> $Middlewares Replaces the default middleware stack wholesale — every entry must be a Middleware.
    * @param null|array<array-key,mixed> $Resources Extra response resources, by name — every value must be a Closure factory.
    * @param null|int|float $deferredTimeout Seconds a deferred response may take before it is cancelled.
    *
    * @throws InvalidArgumentException When both `secure` and `AutoTLS` are given, on a
    *                                  stack entry that is not a Middleware, or on a
    *                                  resource that is not a named Closure factory.
    */
   public function __construct (
      Argument $Named = Argument::Undefined,
      null|string $host = null,
      null|int $port = null,
      null|int $workers = null,
      null|array $secure = null,
      null|string $user = null,
      null|string $group = null,
      null|AutoTLS $AutoTLS = null,
      null|bool $enableHTTP2 = null,
      null|string $health = '/health',
      null|int $maxConnections = null,
      null|int $maxConnectionsPerIP = null,
      null|int $connectionIdleTimeout = null,
      null|array $Middlewares = null,
      null|array $Resources = null,
      null|int|float $deferredTimeout = null
   )
   {
      // ? Validate here, not at apply time — a stack entry that is not a
      //   Middleware would otherwise fail inside a worker, on its first request
      if ($Middlewares !== null) {
         foreach ($Middlewares as $index => $Middleware) {
            if ($Middleware instanceof Middleware === false) {
               throw new InvalidArgumentException(
                  "Middleware stack entry `{$index}` must be a Middleware."
               );
            }
         }
      }
      // ? Same contract as `Response\Configs`, enforced before the App merges
      //   the entries with the resources it provides itself
      if ($Resources !== null) {
         foreach ($Resources as $name => $Factory) {
            if (is_string($name) === false) {
               throw new InvalidArgumentException(
                  'Response resource definitions must be keyed by name.'
               );
            }
            if ($Factory instanceof Closure === false) {
               throw new InvalidArgumentException(
                  "Response resource definition `{$name}` must be a Closure factory."
               );
            }
         }
      }

      // ! Platform defaults — a socket option left out falls back instead of
      //   throwing, which is what makes `new Configs` alone a valid App setup
      parent::__construct(
         host: $host ?? '0.0.0.0',
         port: $port ?? 8080,
         workers: $workers ?? 2,
         secure: $secure,
         user: $user,
         group: $group,
         AutoTLS: $AutoTLS,
         enableHTTP2: $enableHTTP2,
         health: $health,
         maxConnections: $maxConnections,
         maxConnectionsPerIP: $maxConnectionsPerIP,
         connectionIdleTimeout: $connectionIdleTimeout
      );

      // * Config
      /** @var null|array<int,Middleware> $Middlewares — proved by the loop above */
      $this->Middlewares = $Middlewares;
      /** @var null|array<string,Closure> $Resources — proved by the loop above */
      $this->Resources = $Resources;
      $this->deferredTimeout = $deferredTimeout;
   }
}
