<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web;


use Bootgly\WPI;
use Web;


abstract class App
{
   public Web $Web;

   // * Config
   // ...

   // * Data
   // ...

   // * Meta
   // ...


   public function __construct ()
   {
      $Web = $this->Web = new Web;
      // ---
      $Web->App = $this;
      // ---
      // TODO TEMP
      $Web->Request = WPI::$Request;
      $Web->Response = WPI::$Response;
      $Web->Router = WPI::$Router;

      $Web->Response->use('App', $this);
      $Web->Response->use('Web', $Web);
   }

   abstract public function boot ();
}
