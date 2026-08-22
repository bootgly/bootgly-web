<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\ADI\Databases\SQL\Schema\Auxiliaries\Types;
use Bootgly\ADI\Databases\SQL\Schema\Blueprint;
use Bootgly\ADI\Databases\SQL\Schema\Migrating;
use Bootgly\ADI\Databases\SQL\Schema\Migration;


/**
 * Record the epoch second of the latest persistent-login validator rotation.
 */
return new Migration(
   Up: function (Migrating $Schema) {
      return $Schema->alter('trusts', function (Blueprint $Table): void {
         $Rotated = $Table->add('rotated', Types::BigInteger);
         $Rotated->nullable = true;
      });
   },
   Down: function (Migrating $Schema) {
      return $Schema->alter('trusts', function (Blueprint $Table): void {
         $Table->remove('rotated');
      });
   }
);
