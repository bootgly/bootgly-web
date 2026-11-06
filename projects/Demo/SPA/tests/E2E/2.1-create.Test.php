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
   description: 'POST /api/tasks with a JSON object creates a task (201, trimmed title, new id)',

   request: function () {
      return Client::build('POST', '/api/tasks', '{"title":"  Write the E2E specs  "}', 'application/json; charset=utf-8');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $data = Client::decode($response)['data'] ?? null;
      // ?
      if (Client::code($response) !== 201) {
         return 'create did not answer 201, got ' . Client::code($response);
      }
      if (($data['id'] ?? null) !== 4 || ($data['title'] ?? null) !== 'Write the E2E specs' || ($data['done'] ?? null) !== false) {
         return 'unexpected created task: ' . json_encode($data);
      }

      return true;
   }
);
