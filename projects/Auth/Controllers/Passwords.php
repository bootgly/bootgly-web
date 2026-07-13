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


use Bootgly\ADI\Validation;
use Bootgly\ADI\Validators\Confirmed;
use Bootgly\ADI\Validators\Regex;
use Bootgly\ADI\Validators\Required;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


class Passwords extends Controller
{
   /**
    * GET renders the change-password form.
    */
   public function edit (Request $Request, Response $Response): Response
   {
      // :
      return $this->render('auth/password', [
         'token' => $this->token($Request),
         ...$this->pull($Request)
      ]);
   }

   /**
    * POST changes the password after re-proving the current one.
    */
   public function update (Request $Request, Response $Response): Response
   {
      $this->boot($Response);

      // ! Authenticated by the route guard.
      $user = (string) $this->user($Request);

      $current = (string) ($Request->fields['current'] ?? '');
      $password = (string) ($Request->fields['password'] ?? '');

      $Validation = new Validation(
         source: [
            'current' => $current,
            'password' => $password,
            'password_confirmation' => (string) ($Request->fields['password_confirmation'] ?? ''),
         ],
         rules: [
            'current' => [new Required],
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

         return $this->redirect('/password', 303);
      }

      // ? Current-password gate
      if ($this->Users->check($user, $current) === false) {
         $this->fail($Request, ['current' => ['Current password is incorrect.']]);

         return $this->redirect('/password', 303);
      }

      // @ Rotate + full invalidation (core orchestration contract)
      $this->Users->rotate($user, $password);
      $this->Tokens->revoke($user);
      $this->Trust->revoke($user);
      // ! Every trusted device died — including this one's cookie.
      $this->Remember->forget();

      // @ Fresh session id for the surviving session
      $Request->Session->regenerate();

      $this->flash($Request, 'Password changed. Other devices were signed out.');

      // :
      return $this->redirect('/account');
   }
}
