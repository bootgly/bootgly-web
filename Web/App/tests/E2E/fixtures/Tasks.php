<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
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
   public function list (Request $Request, Response $Response): Response
   {
      // ! Deferred, Test environment: a generic Throwable declines to the
      //   core Catcher (byte-exact Test body)
      return $Response->defer(static function (Response $Response): void {
         $Response->wait();
         throw new RuntimeException('secret internals');
      });
   }

   public function create (Request $Request, Response $Response): Response
   {
      // ! One-shot Production override — consumed by the deferred boundary
      Problems::$Environment = Environments::Production;

      return $Response->defer(static function (Response $Response): void {
         $Response->wait();
         throw new RuntimeException('secret internals');
      });
   }

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

   public function delete (Request $Request, Response $Response): Response
   {
      $id = $this->Route->Params->id;

      // ! Deferred: a designed Problem still renders in every environment
      return $Response->defer(static function (Response $Response) use ($id): void {
         $Response->wait();
         throw new Problem(422, detail: "Task {$id} cannot be deleted");
      });
   }
}
