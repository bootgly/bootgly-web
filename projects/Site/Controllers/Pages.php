<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Site\Controllers;


use function in_array;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Web\App\Controller;


class Pages extends Controller
{
   // * Metadata
   /** @var array<int,string> */
   private const array PAGES = ['home', 'about'];


   public function show (Request $Request, Response $Response): Response
   {
      // ! Derive the view from the matched `:page` param ('/' → home)
      $page = $this->Route->Params->page ?? 'home';

      // ? Unknown page
      if (in_array($page, self::PAGES, true) === false) {
         $Response->View->render('errors/404');

         return $Response->code(404);
      }

      // : Wrapped by the default layout (views/layouts/main.template.php)
      return $this->render($page);
   }
}
