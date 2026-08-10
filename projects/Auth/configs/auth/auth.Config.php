<?php

use Bootgly\API\Environment\Configs\Config;
use Bootgly\API\Environment\Configs\Config\Types;


return new Config(scope: 'auth')
   // ! Canonical base for e-mail links — NEVER derived from the Host header
   ->URL->bind(key: 'APP_URL', default: 'http://localhost:8087')
   ->Verification
      ->TTL->bind(key: 'AUTH_VERIFICATION_TTL', default: 86400, cast: Types::Integer)
      ->up()
   ->Recovery
      ->TTL->bind(key: 'AUTH_RECOVERY_TTL', default: 3600, cast: Types::Integer)
      ->up()
   ->Remember
      ->Name->bind(key: 'AUTH_REMEMBER_NAME', default: 'remember')
      ->TTL->bind(key: 'AUTH_REMEMBER_TTL', default: 2592000, cast: Types::Integer)
      ->up();
