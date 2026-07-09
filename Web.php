<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

// Global namespace: `Exception` resolves directly (a `use` import here would
// raise "use statement with non-compound name has no effect").


class Web
{
   // * Config
   // ...

   // * Data
   // ...

   // * Metadata
   private static bool $booted = false;


   /**
    * Autoboot the Web platform.
    *
    * The Web platform is a class library over `Bootgly\WPI`:
    * Apps are booted per project by their `.project.php` signature —
    * there are no process-wide workables to warm here.
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
   }
}
