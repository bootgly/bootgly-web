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
   description: 'PATCH /api/tasks/4 marks the task done and keeps its title',

   request: function () {
      return Client::build('PATCH', '/api/tasks/4', '{"done":true}');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $data = Client::decode($response)['data'] ?? null;
      // ?
      if (Client::code($response) !== 200 || ($data['done'] ?? null) !== true || ($data['title'] ?? null) !== 'Write the E2E specs') {
         return 'patch did not mark task 4 done: ' . json_encode($data);
      }

      return true;
   }
);
