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
 * Retain the immediately previous persistent-login validator digest so a
 * delayed sibling request can be declined without revoking the device series.
 */
return new Migration(
   Up: function (Migrating $Schema) {
      return $Schema->alter('trusts', function (Blueprint $Table): void {
         $Previous = $Table->add('previous', Types::String)->limit(64);
         $Previous->nullable = true;
      });
   },
   Down: function (Migrating $Schema) {
      return $Schema->alter('trusts', function (Blueprint $Table): void {
         $Table->remove('previous');
      });
   }
);
