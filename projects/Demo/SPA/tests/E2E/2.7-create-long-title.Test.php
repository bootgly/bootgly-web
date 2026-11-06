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
   description: 'POST /api/tasks with a 121-character title answers a 422 problem',

   request: function () {
      return Client::build('POST', '/api/tasks', json_encode(['title' => str_repeat('é', 121)]));
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $Body = Client::decode($response);
      // ?
      if (Client::code($response) !== 422) {
         return 'expected 422, got ' . Client::code($response);
      }
      if (str_starts_with((string) Client::read($response, 'Content-Type'), 'application/problem+json') === false) {
         return 'not problem+json: ' . Client::read($response, 'Content-Type');
      }
      if (($Body['status'] ?? null) !== 422 || ($Body['detail'] ?? null) !== 'The title must have at most 120 characters.') {
         return 'unexpected problem: ' . json_encode($Body);
      }

      return true;
   }
);
