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


use function trim;

use Bootgly\ADI\Validation;
use Bootgly\ADI\Validators\Confirmed;
use Bootgly\ADI\Validators\Email;
use Bootgly\ADI\Validators\Regex;
use Bootgly\ADI\Validators\Required;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


class Registrations extends Controller
{
   /**
    * GET renders the sign-up form; POST creates the account, sends the
    * verification link and signs the user in.
    */
   public function create (Request $Request, Response $Response): Response
   {
      // ?: Already signed in
      if ($this->user($Request) !== null) {
         return $this->redirect('/account');
      }

      // ?: GET renders the sign-up form
      if ($Request->method === 'GET') {
         return $this->render('auth/register', [
            'token' => $this->token($Request),
            ...$this->pull($Request)
         ]);
      }

      // @ POST persists
      $this->boot($Response);

      $email = trim((string) ($Request->fields['email'] ?? ''));
      $password = (string) ($Request->fields['password'] ?? '');

      $Validation = new Validation(
         source: [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => (string) ($Request->fields['password_confirmation'] ?? ''),
         ],
         rules: [
            'email' => [new Required, new Email],
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
         $this->fail($Request, $Validation->errors, ['email' => $email]);

         return $this->redirect('/register', 303);
      }

      // @ Enroll — the unique e-mail index is the only duplicate gate
      $user = $this->Users->enroll($email, $password);
      // ?
      if ($user === null) {
         $this->fail($Request, ['email' => ['This e-mail is already registered.']], ['email' => $email]);

         return $this->redirect('/register', 303);
      }

      // @ E-mail verification link
      $this->notify($user, $email);

      // @ Auto-login with a fresh session id
      $Session = $Request->Session;
      $Session->regenerate();
      $Session->set('identity', $user);

      $this->flash($Request, 'Account created! Check your inbox for the verification link.');

      // :
      return $this->redirect('/account');
   }
}
