<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
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

use projects\Auth\Controllers\Accounts;
use projects\Auth\Controllers\Passwords;
use projects\Auth\Controllers\Registrations;
use projects\Auth\Controllers\Resets;
use projects\Auth\Controllers\Sessions;
use projects\Auth\Controllers\Verifications;


return static function (Request $Request, Response $Response, Router $Router): Generator
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
      new RateLimit(limit: 5, window: 600, key: static fn (object $Request): string => "register:{$Request->peer}")
   ]);

   // * Session (sign in / sign out)
   yield $Router->route('/login', new Action(Sessions::class, 'create'), GET);
   yield $Router->route('/login', new Action(Sessions::class, 'create'), POST, middlewares: [
      new RateLimit(limit: 5, window: 60, key: static fn (object $Request): string => "login:{$Request->peer}")
   ]);
   yield $Router->route('/logout', new Action(Sessions::class, 'delete'), POST, middlewares: [$Auth]);

   // * E-mail verification
   yield $Router->route('/verify/:selector<alphanum>/:verifier<alphanum>', new Action(Verifications::class, 'confirm'), GET, middlewares: [
      new RateLimit(limit: 10, window: 60, key: static fn (object $Request): string => "verify:{$Request->peer}")
   ]);
   yield $Router->route('/verify', new Action(Verifications::class, 'create'), POST, middlewares: [
      $Auth,
      new RateLimit(limit: 3, window: 600, key: static fn (object $Request): string => "resend:{$Request->peer}")
   ]);

   // * Password recovery
   yield $Router->route('/forgot', new Action(Resets::class, 'create'), GET);
   yield $Router->route('/forgot', new Action(Resets::class, 'create'), POST, middlewares: [
      new RateLimit(limit: 3, window: 600, key: static fn (object $Request): string => "forgot:{$Request->peer}")
   ]);
   yield $Router->route('/reset/:selector<alphanum>/:verifier<alphanum>', new Action(Resets::class, 'edit'), GET);
   yield $Router->route('/reset/:selector<alphanum>/:verifier<alphanum>', new Action(Resets::class, 'update'), POST, middlewares: [
      new RateLimit(limit: 5, window: 600, key: static fn (object $Request): string => "reset:{$Request->peer}")
   ]);

   // * Password change (signed in)
   yield $Router->route('/password', new Action(Passwords::class, 'edit'), GET, middlewares: [$Auth]);
   yield $Router->route('/password', new Action(Passwords::class, 'update'), POST, middlewares: [
      $Auth,
      new RateLimit(limit: 5, window: 60, key: static fn (object $Request): string => "password:{$Request->peer}")
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
