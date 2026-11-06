<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Demo\SPA\tests\E2E\fixtures;


use function json_decode;
use function preg_match;
use function strlen;
use function strpos;
use function substr;


/**
 * Raw HTTP helpers for the SPA API E2E specs — the harness sends the string
 * a `request:` closure returns and hands the raw response to `test:`.
 */
final class Client
{
   /**
    * Build one raw request, with a body when one is given.
    */
   public static function build (
      string $method, string $path, null|string $body = null, null|string $type = 'application/json'
   ): string
   {
      $request = "{$method} {$path} HTTP/1.1\r\nHost: localhost\r\n";

      // ? No body
      if ($body === null) {
         return "{$request}\r\n";
      }

      if ($type !== null) {
         $request .= "Content-Type: {$type}\r\n";
      }
      $length = strlen($body);

      // :
      return "{$request}Content-Length: {$length}\r\n\r\n{$body}";
   }

   /**
    * Read the response status code (0 when there is none).
    */
   public static function code (string $response): int
   {
      return preg_match('#\AHTTP/1\.\d (\d{3})#', $response, $match) === 1
         ? (int) $match[1]
         : 0;
   }

   /**
    * Read one response header value (null when absent).
    */
   public static function read (string $response, string $name): null|string
   {
      return preg_match("#\r\n{$name}: ([^\r\n]*)#i", $response, $match) === 1
         ? $match[1]
         : null;
   }

   /**
    * Decode the JSON body into arrays (null when it is not JSON).
    */
   public static function decode (string $response): mixed
   {
      $start = strpos($response, "\r\n\r\n");

      // ?
      if ($start === false) {
         return null;
      }

      // :
      return json_decode(substr($response, $start + 4), true);
   }
}
