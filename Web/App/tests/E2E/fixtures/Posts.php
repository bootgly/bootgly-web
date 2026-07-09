<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\App\tests\E2E\fixtures;


use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Web\App\Controller;


class Posts extends Controller
{
   public function list (Request $Request, Response $Response): Response
   {
      return $Response(body: 'posts:list');
   }

   public function show (Request $Request, Response $Response): Response
   {
      // ! Matched route param — read through the Controller Route hook
      $id = $this->Route->Params->id;

      return $Response(body: "posts:show:{$id}");
   }

   public function create (Request $Request, Response $Response): Response
   {
      return $Response(body: "posts:create:{$Request->method}");
   }

   public function edit (Request $Request, Response $Response): Response
   {
      return $Response(body: "posts:edit:{$this->Route->Params->id}");
   }

   public function update (Request $Request, Response $Response): Response
   {
      return $Response(body: "posts:update:{$this->Route->Params->id}");
   }

   public function delete (Request $Request, Response $Response): Response
   {
      return $Response(body: "posts:delete:{$this->Route->Params->id}");
   }
}
