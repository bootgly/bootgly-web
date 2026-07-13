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


use Bootgly\API\Security\Tokens\Purposes;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


class Verifications extends Controller
{
   /**
    * POST resends the verification link for the signed-in account.
    */
   public function create (Request $Request, Response $Response): Response
   {
      $this->boot($Response);

      // ! Authenticated by the route guard.
      $user = (string) $this->user($Request);

      // @ Load the account state
      $Result = $Response->Database->fetch(
         'SELECT email, email_verified_at FROM users WHERE id = $1',
         [$user]
      );
      // ? Stale identity (account gone) — drop the session
      if ($Result->empty) {
         $Request->Session->flush();
         $Request->Session->regenerate();

         return $this->redirect('/login', 303);
      }
      $row = $Result->row;

      // ?: Nothing to verify
      if ($row['email_verified_at'] !== null) {
         $this->flash($Request, 'Your e-mail is already verified.');

         return $this->redirect('/account');
      }

      // @ Mint + send a fresh link (supersedes the previous one)
      $this->notify($user, (string) $row['email']);
      $this->flash($Request, 'Verification link sent. Check your inbox.');

      // :
      return $this->redirect('/account');
   }

   /**
    * GET consumes the e-mailed verification link.
    */
   public function confirm (Request $Request, Response $Response): Response
   {
      $this->boot($Response);

      // ! Route params carry the two token halves.
      $token = "{$this->Route->Params->selector}.{$this->Route->Params->verifier}";

      // @ Single-use redeem
      $user = $this->Tokens->redeem($token, Purposes::Verification);
      $home = $this->user($Request) !== null ? '/account' : '/login';
      // ?
      if ($user === null) {
         $this->flash($Request, 'This verification link is invalid or has expired.');

         return $this->redirect($home, 303);
      }

      // @ Stamp the account
      $this->Users->confirm($user);
      $this->flash($Request, 'E-mail verified!');

      // :
      return $this->redirect($home, 303);
   }
}
