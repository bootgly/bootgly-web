<?php

namespace Web\App;

use function assert;
use function count;
use function str_contains;
use function var_export;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;
use TypeError;

use Bootgly\ABI\Configs as Configuring;
use Bootgly\ACI\Tests\Suite\Test;
use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\WPI\Interfaces\TCP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\AutoTLS;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Configs as ServerConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request\Configs as RequestConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Configs as ResponseConfigs;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use Web\App;

/**
 * `App->configure()` takes typed Configs the way every WPI node does: one
 * `App\Configs` (named arguments only, platform defaults filled in) plus the
 * node Configs the server takes on its own, validated as a whole set before
 * anything is applied. The two node Configs the App composes itself are
 * refused when handed directly, naming `App\Configs` as the way.
 */
return new Test(
   description: 'It should enforce the Configs contract of App->configure()',
   test: function () {
      // ! Statics survive the suite: snapshot every one configure() writes
      $oldHealth = HTTP_Server_CLI::$health;
      $OldResponse = HTTP_Server_CLI::$Response ?? null;
      $oldHTTP2 = HTTP_Server_CLI::$enableHTTP2;
      $oldMaxConnections = HTTP_Server_CLI::$maxConnections;
      $oldMaxConnectionsPerIP = HTTP_Server_CLI::$maxConnectionsPerIP;
      $oldConnectionIdleTimeout = HTTP_Server_CLI::$connectionIdleTimeout;
      $oldDeferredTimeout = Response::$deferredTimeout;
      $oldMaxBodySize = Request::$maxBodySize;
      $OldProtocols = TCP_Server_CLI::$Protocols;

      try {
         // @@ A) Positional construction never binds — the guard slot rejects it
         $thrown = null;
         try {
            // @phpstan-ignore-next-line — the point of the assertion
            new Configs('127.0.0.1', 8080, 1);
         }
         catch (TypeError $Thrown) {
            $thrown = $Thrown->getMessage();
         }

         yield assert(
            assertion: $thrown !== null && str_contains($thrown, '$Named'),
            description: 'a positional App Configs dies on the guard slot — '
               . var_export($thrown, true)
         );

         // @@ B) No Configs at all applies the platform defaults
         $App = new App(Mode: Modes::Test);
         $App->configure();

         $workers = new ReflectionProperty(TCP_Server_CLI::class, 'workers')->getValue($App->Server);

         yield assert(
            assertion: $App->Server->host === '0.0.0.0'
               && $App->Server->port === 8080
               && $workers === 2
               && HTTP_Server_CLI::$health === '/health'
               && count($App->Middlewares) === 4,
            description: 'an empty configure() reaches the server with host 0.0.0.0, port 8080, workers 2 and /health — '
               . var_export([$App->Server->host, $App->Server->port, $workers, HTTP_Server_CLI::$health], true)
         );

         // @@ B2) Every option the App Configs carries reaches the server — one
         //     dropped slot in the composed ServerConfigs would be a silent loss
         $App->configure(new Configs(
            host: '127.0.0.1',
            port: 8097,
            workers: 3,
            secure: ['verify_peer' => false],
            enableHTTP2: false,
            health: '/alive',
            maxConnections: 123,
            maxConnectionsPerIP: 45,
            connectionIdleTimeout: 67,
            deferredTimeout: 9,
            user: 'nobody-probe',
            group: 'nogroup-probe'
        ));
         $secure = new ReflectionProperty(TCP_Server_CLI::class, 'secure')->getValue($App->Server);
         $workers = new ReflectionProperty(TCP_Server_CLI::class, 'workers')->getValue($App->Server);
         $user = new ReflectionProperty(TCP_Server_CLI::class, 'user')->getValue($App->Server);
         $group = new ReflectionProperty(TCP_Server_CLI::class, 'group')->getValue($App->Server);
         $landed = [
            $App->Server->host, $App->Server->port, $workers, $secure['verify_peer'] ?? null,
            HTTP_Server_CLI::$enableHTTP2, HTTP_Server_CLI::$health,
            HTTP_Server_CLI::$maxConnections, HTTP_Server_CLI::$maxConnectionsPerIP, HTTP_Server_CLI::$connectionIdleTimeout,
            Response::$deferredTimeout, $user, $group,
         ];

         yield assert(
            assertion: $landed === ['127.0.0.1', 8097, 3, false, false, '/alive', 123, 45, 67, 9, 'nobody-probe', 'nogroup-probe'],
            description: 'host, port, workers, secure, enableHTTP2, health, maxConnections, maxConnectionsPerIP, connectionIdleTimeout, '
               . 'deferredTimeout, user and group must all land on the server — ' . var_export($landed, true)
         );

         // @@ B2b) AutoTLS travels too: the server refuses, before any I/O, an
         //     Auto-TLS whose validation port is the server port — a dropped slot
         //     would make the very shape the docs teach a silent no-op
         $refused = null;
         try {
            $App->configure(new Configs(port: 80, workers: 1, AutoTLS: new AutoTLS(domains: ['probe.test'], email: 'probe@probe.test', port: 80)));
         }
         catch (RuntimeException $Exception) {
            $refused = $Exception->getMessage();
         }

         yield assert(
            assertion: $refused !== null && str_contains($refused, 'Auto-TLS'),
            description: 'an AutoTLS handed through App Configs must reach the server (refused here on the port clash) — '
               . var_export($refused, true)
         );

         // @@ B3) A later call that only forwards a node Configs refines — the
         //     transport last applied stays as it was
         $App->configure(new RequestConfigs(maxBodySize: 1234));

         yield assert(
            assertion: $App->Server->host === '127.0.0.1' && $App->Server->port === 8097
               && HTTP_Server_CLI::$health === '/alive' && HTTP_Server_CLI::$enableHTTP2 === false
               && Request::$maxBodySize === 1234,
            description: 'a node-only configure() must not reset the transport to the platform defaults — '
               . var_export([$App->Server->host, $App->Server->port, HTTP_Server_CLI::$health, HTTP_Server_CLI::$enableHTTP2, Request::$maxBodySize], true)
         );

         // @@ B4) A later App Configs without Middlewares keeps a stack the
         //     project extended in the meantime — the memo never replays it
         $App->configure(new Configs(port: 8097, workers: 1, Middlewares: [new CSRF]));
         $App->Middlewares[] = new CSRF;
         $App->configure(new RequestConfigs(maxBodySize: 2048));

         yield assert(
            assertion: count($App->Middlewares) === 2 && Request::$maxBodySize === 2048,
            description: 'a node-only configure() must leave a stack extended after the last App Configs untouched — '
               . count($App->Middlewares)
         );

         // @@ C) `health: null` disables the endpoint explicitly
         $App->configure(new Configs(health: null));

         yield assert(
            assertion: HTTP_Server_CLI::$health === null,
            description: 'health: null switches the built-in endpoint off — '
               . var_export(HTTP_Server_CLI::$health, true)
         );

         // @@ D) A node Configs the server takes on its own is forwarded verbatim
         $App->configure(
            new RequestConfigs(maxBodySize: 4321),
            new Configs(port: 8099, workers: 1)
         );

         yield assert(
            assertion: Request::$maxBodySize === 4321 && $App->Server->port === 8099,
            description: 'Request\Configs travels to the server, in any order — '
               . var_export([Request::$maxBodySize, $App->Server->port], true)
         );

         // @@ E) Explicit response resources reach the server registry
         $App->configure(
            new Configs(
               port: 8099,
               workers: 1,
               Resources: ['Probe' => static fn (object $Context): object => $Context]
            )
         );

         yield assert(
            assertion: isSet(HTTP_Server_CLI::$Response->Resources->definitions['Probe']) === true,
            description: 'Resources are composed into the Response Configs the App builds'
         );

         // @@ F) The same Configs class twice is rejected before anything is applied
         $CSRF = new CSRF;

         $thrown = null;
         try {
            $App->configure(
               new Configs(port: 1, workers: 1, Middlewares: [$CSRF]),
               new Configs(port: 2, workers: 1)
            );
         }
         catch (InvalidArgumentException $Thrown) {
            $thrown = $Thrown->getMessage();
         }

         yield assert(
            assertion: $thrown !== null
               && str_contains($thrown, 'received two')
               && $App->Server->port === 8099
               && $App->Middlewares !== [$CSRF],
            description: 'a repeated App Configs leaves the server and the stack untouched — '
               . var_export($thrown, true)
         );

         // @@ G) The node Configs the App composes itself are refused, naming
         //       App\Configs as the way in — the whole set is dropped with them
         foreach ([new ServerConfigs(host: '127.0.0.1', port: 3, workers: 1), new ResponseConfigs(deferredTimeout: 1)] as $Composed) {
            $thrown = null;
            try {
               $App->configure(
                  new Configs(port: 4, workers: 1, Middlewares: [$CSRF]),
                  $Composed
               );
            }
            catch (InvalidArgumentException $Thrown) {
               $thrown = $Thrown->getMessage();
            }

            yield assert(
               assertion: $thrown !== null
                  && str_contains($thrown, $Composed::class)
                  && str_contains($thrown, Configs::class)
                  && $App->Server->port === 8099
                  && $App->Middlewares !== [$CSRF],
               description: 'a composed node Configs is refused and its batch never applied — '
                  . var_export($thrown, true)
            );
         }

         // @@ H) A Configs this shell does not know is rejected
         $Foreign = new class implements Configuring {};

         $thrown = null;
         try {
            $App->configure($Foreign);
         }
         catch (InvalidArgumentException $Thrown) {
            $thrown = $Thrown->getMessage();
         }

         yield assert(
            assertion: $thrown !== null && str_contains($thrown, 'does not accept'),
            description: 'an unsupported Configs is rejected — ' . var_export($thrown, true)
         );

         // @@ I) A stack entry that is not a Middleware is refused at construction
         $thrown = null;
         try {
            new Configs(Middlewares: [$CSRF, 'not-a-middleware']);
         }
         catch (InvalidArgumentException $Thrown) {
            $thrown = $Thrown->getMessage();
         }

         yield assert(
            assertion: $thrown !== null && str_contains($thrown, 'must be a Middleware'),
            description: 'an invalid stack entry is rejected at new — ' . var_export($thrown, true)
         );

         // @@ J) A resource that is not a Closure factory is refused at construction
         $thrown = null;
         try {
            new Configs(Resources: ['bad' => 'not-a-closure']);
         }
         catch (InvalidArgumentException $Thrown) {
            $thrown = $Thrown->getMessage();
         }

         yield assert(
            assertion: $thrown !== null && str_contains($thrown, 'Closure factory'),
            description: 'an invalid resource factory is rejected at new — ' . var_export($thrown, true)
         );

         // @@ K) One TLS source only — the node guard survives the subclass
         $thrown = null;
         try {
            new Configs(
               secure: ['local_cert' => '/dev/null'],
               AutoTLS: new AutoTLS(domains: ['bootgly.test'], email: 'a@bootgly.test')
            );
         }
         catch (InvalidArgumentException $Thrown) {
            $thrown = $Thrown->getMessage();
         }

         yield assert(
            assertion: $thrown !== null && str_contains($thrown, 'never both'),
            description: 'a manual context and Auto-TLS cannot both own the transport — '
               . var_export($thrown, true)
         );
      }
      finally {
         HTTP_Server_CLI::$health = $oldHealth;
         HTTP_Server_CLI::$enableHTTP2 = $oldHTTP2;
         HTTP_Server_CLI::$maxConnections = $oldMaxConnections;
         HTTP_Server_CLI::$maxConnectionsPerIP = $oldMaxConnectionsPerIP;
         HTTP_Server_CLI::$connectionIdleTimeout = $oldConnectionIdleTimeout;
         Response::$deferredTimeout = $oldDeferredTimeout;
         Request::$maxBodySize = $oldMaxBodySize;
         TCP_Server_CLI::$Protocols = $OldProtocols;
         if ($OldResponse !== null) {
            HTTP_Server_CLI::$Response = $OldResponse;
         }
      }
   }
);
