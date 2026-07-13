<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Auth;


use const BOOTGLY_STORAGE_DIR;
use function error_log;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function time;
use Throwable;

use Bootgly\ACI\Mail\Message;
use Bootgly\WPI\Services\Mail;


/**
 * Project mailer — composes the auth messages and picks the delivery lane.
 *
 * Three lanes, selected by the `mail` config scope:
 * 1. `MAIL_HOST` empty (default): zero-setup file sink — the rendered
 *    message lands in `storage/mails/*.eml` (also the E2E capture channel);
 * 2. `MAIL_HOST` set: synchronous SMTP through `WPI\Services\Mail::send()`;
 * 3. `MAIL_HOST` set + `MAIL_QUEUE=1`: enqueued via `Mail::dispatch()` for
 *    the `bootgly queue run mail` worker.
 *
 * Delivery failures are logged, never thrown — the anti-enumeration
 * responses must not change when the mailer misbehaves.
 */
class Mails
{
   // * Metadata
   private static int $sequence = 0;


   /**
    * Compose one templated auth message.
    *
    * @param array<string,mixed> $data
    */
   public static function compose (string $template, string $to, string $subject, array $data): Message
   {
      $Message = new Message;
      $Message->from = self::option('From', 'no-reply@auth.localhost');
      $Message->to = $to;
      $Message->subject = $subject;
      // ! Plain-text fallback always carries the action link.
      $Message->text = "{$subject}: {$data['URL']}";
      $Message->template = $template;
      $Message->data = $data;

      return $Message;
   }

   /**
    * Deliver one message through the configured lane.
    */
   public static function deliver (Message $Message): void
   {
      try {
         // ?: Zero-setup file sink
         if (self::option('Host') === '') {
            self::sink($Message);

            return;
         }

         // ?: Queued delivery — the `mail` queue worker sends it
         if (self::option('Queue', false) === true) {
            Mail::dispatch($Message);

            return;
         }

         // : Synchronous SMTP
         Mail::send($Message);
      }
      catch (Throwable $Throwable) {
         // ! Fail quiet — uniform responses must not leak delivery errors.
         error_log("[Auth demo] mail delivery failed: {$Throwable->getMessage()}");
      }
   }

   /**
    * Write the rendered message to the storage file sink.
    */
   private static function sink (Message $Message): void
   {
      $dir = BOOTGLY_STORAGE_DIR . 'mails/';
      // !
      if (is_dir($dir) === false) {
         mkdir($dir, 0775, true);
      }

      $sequence = ++self::$sequence;
      $file = $dir . time() . "-{$sequence}.eml";

      file_put_contents($file, $Message->render());
   }

   /**
    * Read one value from the `mail` config scope.
    */
   private static function option (string $field, mixed $default = ''): mixed
   {
      $Config = BOOTGLY_PROJECT->Configs?->get('mail');
      // ?
      if ($Config === null) {
         return $default;
      }

      $value = $Config->{$field}->get();

      // :
      return $value ?? $default;
   }
}
