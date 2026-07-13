<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Auth\Controllers;


use function str_replace;
use function trim;

use Bootgly\ADI\Validation;
use Bootgly\ADI\Validators\Confirmed;
use Bootgly\ADI\Validators\Email;
use Bootgly\ADI\Validators\Regex;
use Bootgly\ADI\Validators\Required;
use Bootgly\API\Security\Tokens\Purposes;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Auth\Mails;


class Resets extends Controller
{
   /**
    * GET renders the forgot-password form; POST mints + mails the reset
    * link with a uniform anti-enumeration response.
    */
   public function create (Request $Request, Response $Response): Response
   {
      // ?: GET renders the forgot-password form
      if ($Request->method === 'GET') {
         return $this->render('auth/forgot', [
            'token' => $this->token($Request),
            ...$this->pull($Request)
         ]);
      }

      // @ POST mints + mails
      $this->boot($Response);

      $email = trim((string) ($Request->fields['email'] ?? ''));

      $Validation = new Validation(
         source: ['email' => $email],
         rules: ['email' => [new Required, new Email]]
      );
      // ?
      if ($Validation->valid === false) {
         $this->fail($Request, $Validation->errors, ['email' => $email]);

         return $this->redirect('/forgot', 303);
      }

      // @ Mint + send only for existing accounts — the response never varies
      $Identity = $this->Users->fetch($email);
      if ($Identity !== null) {
         $ttl = (int) $this->option('Recovery', 'TTL', 3600);
         $Token = $this->Tokens->mint($Identity->id, Purposes::Recovery, $ttl);

         $URL = (string) $this->option('', 'URL', 'http://localhost:8087');
         $link = "{$URL}/reset/" . str_replace('.', '/', $Token->value);

         Mails::deliver(Mails::compose(
            template: 'recovery',
            to: $email,
            subject: 'Reset your password',
            data: ['URL' => $link, 'TTL' => $ttl]
         ));
      }

      // : Uniform anti-enumeration response
      $this->flash($Request, 'If that e-mail exists, we sent a password reset link.');

      return $this->redirect('/forgot', 303);
   }

   /**
    * GET renders the new-password form after a non-consuming token peek.
    */
   public function edit (Request $Request, Response $Response): Response
   {
      $this->boot($Response);

      $selector = (string) $this->Route->Params->selector;
      $verifier = (string) $this->Route->Params->verifier;

      // ? Peek only — the token burns on POST, not on the form render
      if ($this->Tokens->check("{$selector}.{$verifier}", Purposes::Recovery) === false) {
         $this->flash($Request, 'This reset link is invalid or has expired.');

         return $this->redirect('/forgot', 303);
      }

      // :
      return $this->render('auth/reset', [
         'token' => $this->token($Request),
         'selector' => $selector,
         'verifier' => $verifier,
         ...$this->pull($Request)
      ]);
   }

   /**
    * POST consumes the reset token, rotates the password and revokes
    * every outstanding token and trusted device.
    */
   public function update (Request $Request, Response $Response): Response
   {
      $this->boot($Response);

      $selector = (string) $this->Route->Params->selector;
      $verifier = (string) $this->Route->Params->verifier;
      $password = (string) ($Request->fields['password'] ?? '');

      $Validation = new Validation(
         source: [
            'password' => $password,
            'password_confirmation' => (string) ($Request->fields['password_confirmation'] ?? ''),
         ],
         rules: [
            // ! Regex length gate — Minimum() reads numeric-only strings as numbers
            'password' => [
               new Required,
               new Regex('/\A.{8,}\z/s', 'password must be at least 8 characters.'),
               new Confirmed
            ],
         ]
      );
      // ?
      if ($Validation->valid === false) {
         $this->fail($Request, $Validation->errors);

         return $this->redirect("/reset/{$selector}/{$verifier}", 303);
      }

      // @ Single-use redeem — burns the link
      $user = $this->Tokens->redeem("{$selector}.{$verifier}", Purposes::Recovery);
      // ?
      if ($user === null) {
         $this->flash($Request, 'This reset link is invalid or has expired.');

         return $this->redirect('/forgot', 303);
      }

      // @ Rotate + full invalidation (core orchestration contract)
      $this->Users->rotate($user, $password);
      $this->Tokens->revoke($user);
      $this->Trust->revoke($user);
      // ! Completing a reset proves mailbox possession.
      $this->Users->confirm($user);

      $this->flash($Request, 'Password reset! Sign in with your new password.');

      // :
      return $this->redirect('/login');
   }
}
