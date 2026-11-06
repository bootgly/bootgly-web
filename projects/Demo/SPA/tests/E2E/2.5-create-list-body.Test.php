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
   description: 'POST /api/tasks with a JSON list answers a 400 problem',

   request: function () {
      return Client::build('POST', '/api/tasks', '["title"]');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $Body = Client::decode($response);
      // ?
      if (Client::code($response) !== 400) {
         return 'expected 400, got ' . Client::code($response);
      }
      if (str_starts_with((string) Client::read($response, 'Content-Type'), 'application/problem+json') === false) {
         return 'not problem+json: ' . Client::read($response, 'Content-Type');
      }
      if (($Body['status'] ?? null) !== 400 || ($Body['detail'] ?? null) !== 'The body must be a JSON object.') {
         return 'unexpected problem: ' . json_encode($Body);
      }

      return true;
   }
);
