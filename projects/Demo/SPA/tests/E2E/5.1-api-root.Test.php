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
   description: 'GET /api answers a 404 problem, never a page',

   request: function () {
      return Client::build('GET', '/api');
   },
   response: require __DIR__ . '/fixtures/app.php',

   test: function ($response) {
      $Body = Client::decode($response);
      // ?
      if (Client::code($response) !== 404) {
         return 'expected 404, got ' . Client::code($response);
      }
      if (str_starts_with((string) Client::read($response, 'Content-Type'), 'application/problem+json') === false) {
         return 'not problem+json: ' . Client::read($response, 'Content-Type');
      }
      if (($Body['status'] ?? null) !== 404 || ($Body['detail'] ?? null) !== 'No API route matches this path.') {
         return 'unexpected problem: ' . json_encode($Body);
      }

      return true;
   }
);
