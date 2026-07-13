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
use function trim;

use Bootgly\ADI\Validation;
use Bootgly\ADI\Validators\Email;
use Bootgly\ADI\Validators\Required;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\Authentication\Remember;


class Sessions extends Controller
{
   /**
    * GET renders the sign-in form; POST authenticates.
    */
   public function create (Request $Request, Response $Response): Response
   {
      // ?: Already signed in
      if ($this->user($Request) !== null) {
         return $this->redirect('/account');
      }

      // ?: GET renders the sign-in form
      if ($Request->method === 'GET') {
         return $this->render('auth/login', [
            'token' => $this->token($Request),
            ...$this->pull($Request)
         ]);
      }

      // @ POST authenticates
      $this->boot($Response);

      $email = trim((string) ($Request->fields['email'] ?? ''));
      $password = (string) ($Request->fields['password'] ?? '');

      $Validation = new Validation(
         source: ['email' => $email, 'password' => $password],
         rules: [
            'email' => [new Required, new Email],
            'password' => [new Required],
         ]
      );
      // ?
      if ($Validation->valid === false) {
         $this->fail($Request, $Validation->errors, ['email' => $email]);

         return $this->redirect('/login', 303);
      }

      // @ Verify credentials (uniform timing on unknown e-mails)
      $Identity = $this->Users->verify($email, $password);
      // ? Uniform failure — never reveals which half was wrong
      if ($Identity === null) {
         $this->flash($Request, 'Invalid credentials.');
         $this->fail($Request, [], ['email' => $email]);

         return $this->redirect('/login', 303);
      }

      // @ Session fixation defense + identity
      $Session = $Request->Session;
      $Session->regenerate();
      $Session->set('identity', $Identity->id);

      // ?: Trusted device (remember-me) — only with the checkbox
      if (isSet($Request->fields['remember'])) {
         $this->Remember->emit(
            $this->Trust->issue($Identity->id, Remember::$lifetime)
         );
      }

      $this->flash($Request, 'Welcome back!');

      // : Intended URL captured by the guard fallback — or the account page
      $intended = $Session->pull('intended');
      $target = is_string($intended) && $intended !== '' ? $intended : '/account';

      return $this->redirect($target, 303);
   }

   /**
    * POST signs out: drops this device's trust series, clears the cookie
    * and destroys the session.
    */
   public function delete (Request $Request, Response $Response): Response
   {
      $this->boot($Response);

      // @ Drop this device's trust series + remember cookie
      $cookie = $Request->Cookies->get(Remember::$name);
      if ($cookie !== '') {
         $this->Trust->forget($cookie);
      }
      $this->Remember->forget();

      // @ Kill the session
      $Session = $Request->Session;
      $Session->flush();
      $Session->regenerate();

      $this->flash($Request, 'Signed out.');

      // :
      return $this->redirect('/login');
   }
}
