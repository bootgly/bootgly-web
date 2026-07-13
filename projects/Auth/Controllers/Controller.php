<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Auth\Controllers;


use function is_string;
use function str_replace;

use Bootgly\API\Security\Password;
use Bootgly\API\Security\Tokens;
use Bootgly\API\Security\Tokens\Purposes;
use Bootgly\API\Security\Tokens\Trust;
use Bootgly\API\Security\Users;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication\Remember;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use projects\Auth\Mails;
use Web\App\Controller as Shell;


/**
 * Auth demo controller base — wires the core security stores to the
 * request-scoped Database resource and shares the flash/PRG helpers.
 */
abstract class Controller extends Shell
{
   // * Data
   protected Users $Users;
   protected Tokens $Tokens;
   protected Trust $Trust;
   protected Remember $Remember;

   // * Metadata
   // ...


   /**
    * Wire the core security stores to this request's Database resource.
    */
   protected function boot (Response $Response): void
   {
      $Database = $Response->Database->Database;

      $this->Users = new Users($Database, new Password);
      $this->Tokens = new Tokens($Database);
      $this->Trust = new Trust($Database);
      $this->Remember = new Remember($this->Trust);
   }

   /**
    * Read the authenticated account id from the session.
    */
   protected function user (Request $Request): null|string
   {
      $identity = $Request->Session->get('identity');

      // :
      return is_string($identity) && $identity !== '' ? $identity : null;
   }

   /**
    * Per-render masked CSRF token (BREACH mitigation) for form fields.
    */
   protected function token (Request $Request): string
   {
      // :
      return CSRF::mask((string) $Request->Session->get('_csrf_token', ''));
   }

   /**
    * Flash one status message through the session (PRG pattern).
    */
   protected function flash (Request $Request, string $message): void
   {
      $Request->Session->set('flash', $message);
   }

   /**
    * Flash validation errors + safe old input for the next form render.
    *
    * @param array<string,array<int,string>> $errors
    * @param array<string,string> $old Safe fields only — never passwords.
    */
   protected function fail (Request $Request, array $errors, array $old = []): void
   {
      $Request->Session->set('errors', $errors);
      $Request->Session->set('old', $old);
   }

   /**
    * Pull the flashed form state (errors + old input) for a form render.
    *
    * @return array{errors:array<string,array<int,string>>, old:array<string,string>}
    */
   protected function pull (Request $Request): array
   {
      // :
      return [
         'errors' => (array) $Request->Session->pull('errors', []),
         'old' => (array) $Request->Session->pull('old', []),
      ];
   }

   /**
    * Mint + send one e-mail verification link.
    *
    * The link is built from the configured `APP_URL` only — never from
    * the request Host header (reset/verification link poisoning).
    */
   protected function notify (string $user, string $email): void
   {
      $ttl = (int) $this->option('Verification', 'TTL', 86400);
      $Token = $this->Tokens->mint($user, Purposes::Verification, $ttl);

      $URL = (string) $this->option('', 'URL', 'http://localhost:8087');
      $link = "{$URL}/verify/" . str_replace('.', '/', $Token->value);

      Mails::deliver(Mails::compose(
         template: 'verification',
         to: $email,
         subject: 'Verify your e-mail',
         data: ['URL' => $link, 'TTL' => $ttl]
      ));
   }

   /**
    * Read one value from the `auth` config scope.
    */
   protected function option (string $section, string $field, mixed $default): mixed
   {
      $Config = BOOTGLY_PROJECT->Configs?->get('auth');
      // ?
      if ($Config === null) {
         return $default;
      }

      $value = $section === ''
         ? $Config->{$field}->get()
         : $Config->{$section}->{$field}->get();

      // :
      return $value ?? $default;
   }
}
