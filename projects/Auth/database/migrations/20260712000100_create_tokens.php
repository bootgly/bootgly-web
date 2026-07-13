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
 * Single-use action tokens (`Bootgly\API\Security\Tokens` store):
 * selector (public half, 16 hex) + sha256 digest of the verifier.
 */
return new Migration(
   Up: function (Migrating $Schema) {
      return [
         $Schema->create('tokens', function (Blueprint $Table): void {
            $Table->add('id', Types::BigInteger)
               ->generate()
               ->constrain(Keys::Primary);
            $Table->add('selector', Types::String)
               ->limit(16)
               ->constrain(Keys::Unique);
            // ! sha256 hex digest of the verifier — never the raw secret
            $Table->add('verifier', Types::String)
               ->limit(64);
            $Table->add('user_id', Types::BigInteger)
               ->reference('users', 'id');
            // ! Purposes enum backing values: 'recovery' | 'verification'
            $Table->add('purpose', Types::String)
               ->limit(20);
            // ! Epoch seconds
            $Table->add('expires', Types::BigInteger);
            $Table->add('created_at', Types::Timestamp)->default = new Expression('CURRENT_TIMESTAMP');
         }),
         $Schema->index('tokens', ['user_id', 'purpose'])
      ];
   },
   Down: function (Migrating $Schema) {
      return $Schema->drop('tokens');
   }
);
