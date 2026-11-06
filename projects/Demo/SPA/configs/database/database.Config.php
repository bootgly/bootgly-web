<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\API\Environment\Configs\Config;
use Bootgly\API\Environment\Configs\Config\Types;


return new Config(scope: 'database')
   ->Enabled->bind(key: 'DB_ENABLED', default: true, cast: Types::Boolean)
   ->Default->bind(key: '', default: 'sqlite')
   ->Connections
      ->SQLite
         ->Driver->bind(key: '', default: 'sqlite')
         ->Database->bind(key: 'DB_NAME', default: __DIR__ . '/../../database/spa.sqlite')
         ->up()
      ->up();
