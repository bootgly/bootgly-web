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
   description: 'GET /api/tasks lists the seeded tasks in id order, with typed fields',

   request: function () {
      return Client::build('GET', '/api/tasks');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $Body = Client::decode($response);
      $data = $Body['data'] ?? null;
      // ?
      if (Client::code($response) !== 200 || is_array($data) === false || count($data) !== 3) {
         return 'list did not answer 200 with the 3 seeded tasks';
      }
      if (array_column($data, 'id') !== [1, 2, 3]) {
         return 'list is not ordered by id: ' . json_encode(array_column($data, 'id'));
      }
      if ($data[0]['done'] !== true || $data[1]['done'] !== false) {
         return '`done` is not a boolean';
      }
      if (str_starts_with((string) Client::read($response, 'Content-Type'), 'application/json') === false) {
         return 'list is not application/json';
      }

      return true;
   }
);
