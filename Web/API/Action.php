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


use function method_exists;
use InvalidArgumentException;


/**
 * Invokable lazy controller-action dispatcher.
 *
 * The core Router types handlers as `callable`: an `Action` instance
 * satisfies it while deferring controller construction to request time —
 * and, not being a `Closure`, it is never re-bound by the Router
 * (the no-middleware path wraps it once via `Closure::fromCallable()`).
 *
 * A fresh controller instance is created per request: the HTTP server is
 * preforked and long-running, so a persistent controller would carry
 * mutable state across requests.
 */
class Action
{
   // * Config
   /** @var class-string */
   public private(set) string $controller;
   public private(set) string $action;


   /**
    * @param class-string $controller The controller class to instantiate at dispatch time.
    * @param string $action The action (method) to call on the controller.
    *
    * @throws InvalidArgumentException When the action does not exist on the controller.
    */
   public function __construct (string $controller, string $action)
   {
      // ? Fail loud at registration time — never at request time
      if (method_exists($controller, $action) === false) {
         throw new InvalidArgumentException(
            "Action `{$controller}::{$action}` does not exist."
         );
      }

      // * Config
      $this->controller = $controller;
      $this->action = $action;
   }

   /**
    * Dispatch the action — fresh controller instance per request.
    */
   public function __invoke (object $Request, object $Response): mixed
   {
      // !
      $Controller = new ($this->controller);

      // :
      return $Controller->{$this->action}($Request, $Response);
   }
}
