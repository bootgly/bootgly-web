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


use RuntimeException;

use Bootgly\API\Environments;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Web\API\Problem;
use Web\API\Problems;
use Web\App\Controller;


class Tasks extends Controller
{
   public function show (Request $Request, Response $Response): Response
   {
      $id = $this->Route->Params->id;

      throw new Problem(422, detail: "Task {$id} is not processable");
   }

   public function update (Request $Request, Response $Response): Response
   {
      // ! One-shot Production override — consumed by the Problems boundary
      Problems::$Environment = Environments::Production;

      throw new RuntimeException('secret internals');
   }
}
