<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
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
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middleware;


/**
 * problem+json error boundary for REST route groups.
 *
 * Narrows the error representation to `application/problem+json`:
 * a thrown `Problem` renders in every environment (it is a designed API
 * response, not a defect); a generic `Throwable` is rethrown to the core
 * Catcher in Development/Test (debug page / byte-exact Test bodies) and
 * rendered as an internals-free 500 problem in Production/Staging —
 * reported through the single `Throwables::notify()` intake.
 */
class Problems implements Middleware
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
         // ! Environment — one-shot override consumed here
         $Environment = self::$Environment ?? SAPI::$Environment;
         self::$Environment = null;

         // ? Development / Test — the core Catcher owns disclosure
         if ($Environment === Environments::Development || $Environment === Environments::Test) {
            throw $Throwable;
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
}
