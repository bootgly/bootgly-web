<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Auth\tests\E2E\fixtures;


use const BOOTGLY_STORAGE_DIR;
use function count;
use function file_get_contents;
use function glob;
use function http_build_query;
use function implode;
use function preg_match;
use function preg_match_all;
use function quoted_printable_decode;
use function sort;
use function strlen;


/**
 * Client-side state registry for the serial auth E2E specs.
 *
 * Every `request:`/`test:` closure runs in the master test process, so a
 * static registry carries cookies, CSRF tokens and captured mail links
 * from one spec to the next (the harness has no cookie jar).
 */
final class State
{
   // * Data
   /** Session cookie — `PHPSID=<id>`. */
   public static string $session = '';
   /** Masked CSRF token harvested from the last form render. */
   public static string $token = '';
   /** Remember cookie value — `<selector>.<validator>`. */
   public static string $remember = '';
   /** Pre-rotation remember value kept for the theft-replay spec. */
   public static string $stale = '';
   /** Mail-sink file count snapshot (anti-enumeration spec). */
   public static int $mails = 0;

   // * Metadata
   // ...


   /**
    * Build one raw GET request.
    *
    * @param array<int,string> $cookies
    */
   public static function get (string $path, array $cookies = []): string
   {
      $request = "GET {$path} HTTP/1.1\r\nHost: localhost\r\n";
      if ($cookies !== []) {
         $request .= 'Cookie: ' . implode('; ', $cookies) . "\r\n";
      }

      // :
      return "{$request}\r\n";
   }

   /**
    * Build one raw urlencoded POST request.
    *
    * @param array<string,string> $fields
    * @param array<int,string> $cookies
    */
   public static function post (string $path, array $fields, array $cookies = []): string
   {
      $body = http_build_query($fields);

      $request = "POST {$path} HTTP/1.1\r\nHost: localhost\r\n";
      if ($cookies !== []) {
         $request .= 'Cookie: ' . implode('; ', $cookies) . "\r\n";
      }
      $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
      $request .= 'Content-Length: ' . strlen($body) . "\r\n\r\n";

      // :
      return $request . $body;
   }

   /**
    * Read the response status code.
    */
   public static function code (string $response): int
   {
      // :
      return preg_match('#\AHTTP/1\.\d (\d{3})#', $response, $match) === 1
         ? (int) $match[1]
         : 0;
   }

   /**
    * Read one Set-Cookie value from a raw response ('' when absent).
    */
   public static function cookie (string $response, string $name): null|string
   {
      // ?
      if (preg_match("#Set-Cookie: {$name}=([^;\r\n]*)#", $response, $match) !== 1) {
         return null;
      }

      // :
      return $match[1];
   }

   /**
    * Absorb the response cookies into the registry.
    */
   public static function absorb (string $response): void
   {
      // ?: Session id — the LAST Set-Cookie wins (regenerate re-emits)
      if (preg_match_all('#Set-Cookie: PHPSID=([a-f0-9]+)#', $response, $matches) > 0) {
         $ids = $matches[1];
         self::$session = 'PHPSID=' . $ids[count($ids) - 1];
      }

      // ?: Remember cookie — only non-empty values (clears are asserted apart)
      $remember = self::cookie($response, 'remember');
      if ($remember !== null && $remember !== '') {
         self::$remember = $remember;
      }
   }

   /**
    * Harvest the masked CSRF token from a form render.
    */
   public static function harvest (string $response): void
   {
      if (preg_match('#name="_token" value="([a-f0-9]+)"#', $response, $match) === 1) {
         self::$token = $match[1];
      }
   }

   /**
    * Count the captured sink mails.
    */
   public static function count (): int
   {
      // :
      return count(glob(BOOTGLY_STORAGE_DIR . 'mails/*.eml') ?: []);
   }

   /**
    * Extract the newest captured mail link path (`/verify/...` or `/reset/...`).
    */
   public static function link (string $kind): string
   {
      $files = glob(BOOTGLY_STORAGE_DIR . 'mails/*.eml') ?: [];
      // ?
      if ($files === []) {
         return '';
      }
      sort($files);
      $newest = $files[count($files) - 1];

      // ! Quoted-printable soft breaks split the 64-hex verifier mid-line.
      $mail = quoted_printable_decode((string) file_get_contents($newest));

      // ?
      if (preg_match("#(/{$kind}/[a-f0-9]{16}/[a-f0-9]{64})#", $mail, $match) !== 1) {
         return '';
      }

      // :
      return $match[1];
   }
}
