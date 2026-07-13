<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\ADI\Databases\SQL\Builder\Expression;
use Bootgly\ADI\Databases\SQL\Schema\Auxiliaries\Keys;
use Bootgly\ADI\Databases\SQL\Schema\Auxiliaries\Types;
use Bootgly\ADI\Databases\SQL\Schema\Blueprint;
use Bootgly\ADI\Databases\SQL\Schema\Migrating;
use Bootgly\ADI\Databases\SQL\Schema\Migration;


/**
 * Persistent-login device series (`Bootgly\API\Security\Tokens\Trust` store):
 * the selector (series) stays stable, the validator digest rotates on use.
 */
return new Migration(
   Up: function (Migrating $Schema) {
      return [
         $Schema->create('trusts', function (Blueprint $Table): void {
            $Table->add('id', Types::BigInteger)
               ->generate()
               ->constrain(Keys::Primary);
            $Table->add('selector', Types::String)
               ->limit(16)
               ->constrain(Keys::Unique);
            // ! sha256 hex digest of the rotating validator
            $Table->add('verifier', Types::String)
               ->limit(64);
            $Table->add('user_id', Types::BigInteger)
               ->reference('users', 'id');
            // ! Epoch seconds
            $Table->add('expires', Types::BigInteger);
            $Table->add('created_at', Types::Timestamp)->default = new Expression('CURRENT_TIMESTAMP');
         }),
         $Schema->index('trusts', 'user_id')
      ];
   },
   Down: function (Migrating $Schema) {
      return $Schema->drop('trusts');
   }
);
