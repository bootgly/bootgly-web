<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */


use Exception;


use Web\API;


class Web
{
   // * Config
   // ...

   // * Data
   // ...

   // * Metadata
   private static bool $booted = false;


   /**
    * Autoboot Web workables.
    *
    * @return void
    *
    * @throws Exception
    */
   public function autoboot (): void
   {
      // ?
      if (self::$booted)
         throw new Exception("Web has already been booted.");

      // * Metadata
      self::$booted = true;

      // !
      /** @var API $API */
      [
         $API
      ] = require(__DIR__ . '/Web/autoload.php');

      // @
      $API->autoboot();
   }
}
