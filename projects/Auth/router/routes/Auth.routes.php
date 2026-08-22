<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

/**
 * Auth routes — session/cookie authentication flows.
 *
 * Every RateLimit gets a route-scoped `key:` closure: instances share one
 * `ratelimit:` cache namespace, so unscoped keys would pool counters
 * across routes.
 */

use Bootgly\ABI\Resources\Cache;
use Bootgly\API\Security\Tokens\Trust;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authenticating;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication\Remember;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication\Session as SessionGuard;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\RateLimit;
use Web\API\Action;
use Web\App\Statics;

use Auth\Controllers\Accounts;
use Auth\Controllers\Passwords;
use Auth\Controllers\Registrations;
use Auth\Controllers\Resets;
use Auth\Controllers\Sessions;
use Auth\Controllers\Verifications;


// ! The native E2E suite supplies an isolated System V segment so its
//   counters never clear or inherit the live application's shared cache.
$segment = getenv('AUTH_E2E_RATE_LIMIT_SEGMENT');
$segmentID = $segment === false ? 0 : (int) $segment;
if (
   $segment !== false
   && (
      ctype_digit($segment) === false
      || $segmentID < 10_000_000
      || $segmentID > 2_000_000_000
   )
) {
   throw new RuntimeException('AUTH_E2E_RATE_LIMIT_SEGMENT is invalid.');
}
$RateLimitCache = $segmentID === 0
   ? null
   : new Cache([
      'driver' => 'shared',
      'prefix' => 'ratelimit:',
      'segment' => $segmentID,
   ]);

return static function (Request $Request, Response $Response, Router $Router) use ($RateLimitCache): Generator
{

   // ! Guards — Session first (cheap), Remember revival on session miss;
   //   guests get a real 303 to the sign-in page with the intended URL kept.
   $Trust = new Trust($Response->Database->Database);
   $Auth = new Authentication(
      new Authenticating(new SessionGuard, new Remember($Trust)),
      Fallback: function (Request $Request, Response $Response): Response {
         $Request->Session->set('intended', $Request->URI);
         $Request->Session->set('flash', 'Sign in to continue.');

         return $Response->redirect('/login', 303);
      }
   );

   // * Home
   yield $Router->route('/', function (Request $Request, Response $Response) {
      $target = $Request->Session->check('identity') ? '/account' : '/login';

      return $Response->redirect($target, 307);
   }, GET);

   // * Registration
   yield $Router->route('/register', new Action(Registrations::class, 'create'), GET);
   yield $Router->route('/register', new Action(Registrations::class, 'create'), POST, middlewares: [
      new RateLimit(
         limit: 5,
         window: 600,
         key: static fn (object $Request): string => "register:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);

   // * Session (sign in / sign out)
   yield $Router->route('/login', new Action(Sessions::class, 'create'), GET);
   yield $Router->route('/login', new Action(Sessions::class, 'create'), POST, middlewares: [
      new RateLimit(
         limit: 5,
         window: 60,
         key: static fn (object $Request): string => "login:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);
   yield $Router->route('/logout', new Action(Sessions::class, 'delete'), POST, middlewares: [$Auth]);

   // * E-mail verification
   yield $Router->route('/verify/:selector<alphanum>/:verifier<alphanum>', new Action(Verifications::class, 'confirm'), GET, middlewares: [
      new RateLimit(
         limit: 10,
         window: 60,
         key: static fn (object $Request): string => "verify:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);
   yield $Router->route('/verify', new Action(Verifications::class, 'create'), POST, middlewares: [
      $Auth,
      new RateLimit(
         limit: 3,
         window: 600,
         key: static fn (object $Request): string => "resend:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);

   // * Password recovery
   yield $Router->route('/forgot', new Action(Resets::class, 'create'), GET);
   yield $Router->route('/forgot', new Action(Resets::class, 'create'), POST, middlewares: [
      new RateLimit(
         limit: 3,
         window: 600,
         key: static fn (object $Request): string => "forgot:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);
   yield $Router->route('/reset/:selector<alphanum>/:verifier<alphanum>', new Action(Resets::class, 'edit'), GET);
   yield $Router->route('/reset/:selector<alphanum>/:verifier<alphanum>', new Action(Resets::class, 'update'), POST, middlewares: [
      new RateLimit(
         limit: 5,
         window: 600,
         key: static fn (object $Request): string => "reset:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);

   // * Password change (signed in)
   yield $Router->route('/password', new Action(Passwords::class, 'edit'), GET, middlewares: [$Auth]);
   yield $Router->route('/password', new Action(Passwords::class, 'update'), POST, middlewares: [
      $Auth,
      new RateLimit(
         limit: 5,
         window: 60,
         key: static fn (object $Request): string => "password:{$Request->peer}",
         Cache: $RateLimitCache
      )
   ]);

   // * Account
   yield $Router->route('/account', new Action(Accounts::class, 'show'), GET, middlewares: [$Auth]);

   // * Commons
   yield $Router->route('/statics/:file*', new Statics, GET);

   // * Fallback
   yield $Router->route('/*', function (Request $Request, Response $Response) {
      $Response->View->render('errors/404');

      return $Response->code(404);
   }, GET);
};
