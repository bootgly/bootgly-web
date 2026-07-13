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


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;


class Accounts extends Controller
{
   /**
    * GET shows the signed-in account dashboard.
    */
   public function show (Request $Request, Response $Response): Response
   {
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

      // :
      return $this->render('account/show', [
         'email' => (string) $row['email'],
         'verified' => $row['email_verified_at'] !== null,
         'token' => $this->token($Request)
      ]);
   }
}
