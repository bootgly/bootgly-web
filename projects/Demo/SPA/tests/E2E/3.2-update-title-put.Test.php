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
   description: 'PUT /api/tasks/4 renames the task (120 characters accepted) and keeps it done',

   request: function () {
      return Client::build('PUT', '/api/tasks/4', json_encode(['title' => str_repeat('é', 120)]));
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $data = Client::decode($response)['data'] ?? null;
      // ?
      if (Client::code($response) !== 200 || ($data['title'] ?? null) !== str_repeat('é', 120) || ($data['done'] ?? null) !== true) {
         return 'put did not rename task 4: ' . json_encode($data);
      }

      return true;
   }
);
