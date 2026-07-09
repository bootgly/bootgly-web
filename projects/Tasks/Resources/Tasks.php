<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Tasks\Resources;


use function is_object;

use Web\API\Resource;


class Tasks extends Resource
{
   public function transform (object|array $Entity): array
   {
      // : Normalize Task model or row array into the public API shape
      $id = is_object($Entity) === true ? $Entity->id : $Entity['id'];
      $title = is_object($Entity) === true ? $Entity->title : $Entity['title'];
      $done = is_object($Entity) === true ? $Entity->done : $Entity['done'];

      return [
         'id' => (int) $id,
         'title' => (string) $title,
         'done' => (bool) $done
      ];
   }
}
