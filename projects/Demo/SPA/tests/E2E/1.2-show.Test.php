<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test;
use Demo\SPA\tests\E2E\fixtures\Client;

require_once __DIR__ . '/fixtures/Client.php';

return new Test(
   description: 'GET /api/tasks/2 shows one task',

   request: function () {
      return Client::build('GET', '/api/tasks/2');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $data = Client::decode($response)['data'] ?? null;
      // ?
      if (Client::code($response) !== 200 || ($data['id'] ?? null) !== 2 || ($data['done'] ?? null) !== false) {
         return 'show did not answer task 2';
      }

      return true;
   }
);
