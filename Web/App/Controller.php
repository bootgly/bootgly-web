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


use const Bootgly\WPI;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Route;


/**
 * MVC controller base.
 *
 * Actions receive `(Request $Request, Response $Response)` as arguments —
 * the same calling convention as closure handlers. No request state is
 * stored on the instance: a fresh controller is constructed per request
 * (see `Web\API\Action`) and the matched route is read live from the
 * server, so a leaked instance can never hold a stale request.
 *
 * Controller classes are plural nouns of their resource (`class Posts
 * extends Controller`); actions are single-word verbs:
 * `list`, `show`, `create`, `edit`, `update`, `delete`.
 */
abstract class Controller
{
   // * Metadata
   /** Current matched route (params via `$this->Route->Params`). */
   public Route $Route {
      get => WPI->Router->Route;
   }


   /**
    * Render a project view through the Response View resource.
    *
    * @param string $view The view name (relative to the project `views/`).
    * @param null|array<string,mixed> $data Variables exported to the view.
    * @param null|string|false $layout Layout override: null uses the configured
    *   default, false/'' renders bare, a name selects that layout.
    */
   protected function render (string $view, null|array $data = null, null|string|false $layout = null): Response
   {
      // :
      return WPI->Response->View->render($view, $data, layout: $layout);
   }

   /**
    * Redirect through the Response.
    */
   protected function redirect (string $URI, null|int $code = null): Response
   {
      // :
      return WPI->Response->redirect($URI, $code);
   }
}
