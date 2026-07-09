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


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


/**
 * View conventions for the application shell.
 *
 * The core View resource already renders layouts (`@extends`), jails view
 * paths and exports variables — this module only encodes the platform
 * conventions: a default layout at `views/layouts/main.template.php` and
 * variables shared with every render.
 */
class Views
{
   // * Config
   /** Default layout wrapping every rendered view. Empty disables it. */
   public string $layout = 'layouts/main';
   /** @var array<string,mixed> Variables exported to every render. */
   public array $shared = [];


   /**
    * Merge shared view variables.
    *
    * @param array<string,mixed> $variables
    */
   public function share (array $variables): self
   {
      // @
      foreach ($variables as $key => $value) {
         $this->shared[$key] = $value;
      }

      // :
      return $this;
   }

   /**
    * Apply the layout + shared exports onto the Response View resource.
    */
   public function apply (Response $Response): void
   {
      // @
      $Response->View->layout = $this->layout;

      if ($this->shared !== []) {
         $Response->View->export($this->shared);
      }
   }
}
