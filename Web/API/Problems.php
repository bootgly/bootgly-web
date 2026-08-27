<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\API;


use Closure;
use Throwable;

use Bootgly\ABI\Debugging\Data\Throwables;
use Bootgly\API\Environments;
use Bootgly\API\Workables\Server as SAPI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response\Timeout;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Recovering;


/**
 * problem+json error boundary for REST route groups.
 *
 * Narrows the error representation to `application/problem+json`:
 * a thrown `Problem` renders in every environment (it is a designed API
 * response, not a defect); a generic `Throwable` is rethrown to the core
 * Catcher in Development/Test (debug page / byte-exact Test bodies) and
 * rendered as an internals-free 500 problem in Production/Staging —
 * reported through the single `Throwables::notify()` intake.
 *
 * The same policy covers deferred work: a Throwable raised inside
 * `$Response->defer()` reaches `recover()` instead of the `catch` around
 * `$next` (the onion has already returned by then). There a `Problem`
 * renders, the deferral budget (`Response\Timeout`) becomes a 503 problem,
 * and a generic Throwable follows the environment rule — declined to the
 * core Catcher in Development/Test, an internals-free 500 problem otherwise.
 */
class Problems implements Recovering
{
   // * Config
   /**
    * One-shot environment override — consumed by the Throwable branch, so an
    * E2E spec can exercise the Production path without leaking state into
    * the persistent test worker. Mirrors `Catcher::$Environment`.
    */
   public static null|Environments $Environment = null;


   /**
    * @param Request $Request
    * @param Response $Response
    */
   public function process (object $Request, object $Response, Closure $next): object
   {
      // @
      try {
         return $next($Request, $Response);
      }
      catch (Problem $Problem) {
         // : Explicit problems render in every environment
         return $Problem->render($Response);
      }
      catch (Throwable $Throwable) {
         // ?: Development / Test — rethrown: the core Catcher owns disclosure
         return $this->narrow($Request, $Response, $Throwable) ?? throw $Throwable;
      }
   }

   /**
    * The deferred-work side of the boundary: a `Problem` renders in every
    * environment, the deferral budget becomes a 503 problem, and any other
    * Throwable follows the environment rule — declined to the core Catcher in
    * Development/Test, a reported, internals-free 500 problem otherwise.
    */
   public function recover (Request $Request, Response $Response, Throwable $Throwable): null|Response
   {
      // ?: Explicit problems render in every environment
      if ($Throwable instanceof Problem) {
         return $Throwable->render($Response);
      }
      // ?: The deferral budget is a server policy the core already logged
      if ($Throwable instanceof Timeout) {
         return new Problem(503)->render($Response);
      }

      // :? Development / Test — declined: the core Catcher owns disclosure
      return $this->narrow($Request, $Response, $Throwable);
   }

   /**
    * The environment policy for a generic Throwable: an internals-free 500
    * problem in Production/Staging, reported through the single intake, or
    * null in Development/Test — where the core Catcher owns disclosure.
    *
    * @param Request $Request
    */
   private function narrow (object $Request, Response $Response, Throwable $Throwable): null|Response
   {
      // ! Environment — one-shot override consumed here
      $Environment = self::$Environment ?? SAPI::$Environment;
      self::$Environment = null;

      // ?: Development / Test
      if ($Environment === Environments::Development || $Environment === Environments::Test) {
         return null;
      }

      // @ Production / Staging — report through the single intake
      Throwables::notify($Throwable, [
         'interface' => 'WPI',
         'method' => $Request->method,
         'URI' => $Request->URI,
         'peer' => $Request->peer
      ]);

      // : Internals-free problem
      return new Problem(500)->render($Response);
   }
}
