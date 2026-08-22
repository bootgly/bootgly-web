<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\ADI\Databases\SQL\Builder\Query;
use Bootgly\ADI\Databases\SQL\Schema\Migrating;
use Bootgly\ADI\Databases\SQL\Schema\Migration;


/**
 * Make one action token row authoritative for each user-purpose pair.
 *
 * Existing duplicates are collapsed deterministically to the greatest row id
 * before the unique index is installed. The original non-unique index remains
 * in place so MySQL's non-transactional DDL needs only one schema transition
 * and rollback can remove only what this migration added.
 */
return new Migration(
   Up: function (Migrating $Schema) {
      return [
         new Query(<<<SQL
         DELETE FROM tokens
         WHERE id NOT IN (
            SELECT id
            FROM (
               SELECT MAX(id) AS id
               FROM tokens
               GROUP BY user_id, purpose
            ) AS bootgly_token_winners
         )
         SQL),
         $Schema->index(
            'tokens',
            ['user_id', 'purpose'],
            name: 'tokens_user_id_purpose_unique',
            unique: true
         ),
      ];
   },
   Down: function (Migrating $Schema) {
      return $Schema->unindex('tokens', 'tokens_user_id_purpose_unique');
   }
);
