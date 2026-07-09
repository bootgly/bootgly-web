<?php

namespace Web\API;


use function assert;
use function is_object;
use function strtoupper;

use Bootgly\ACI\Tests\Suite\Test\Specification;


return new Specification(
   description: 'It should transform single entities and collect entity lists',
   test: function () {
      // ! Fixture resource — shapes a task entity
      $Resource = new class extends Resource {
         public function transform (object|array $Entity): array
         {
            // : Normalize object or row array into the public API shape
            $id = is_object($Entity) === true ? $Entity->id : $Entity['id'];
            $name = is_object($Entity) === true ? $Entity->name : $Entity['name'];

            return [
               'id' => $id,
               'name' => strtoupper((string) $name)
            ];
         }
      };

      // @ Single entity (row array)
      $shaped = $Resource->transform(['id' => 1, 'name' => 'write docs', 'secret' => 'x']);

      yield assert(
         assertion: $shaped === ['id' => 1, 'name' => 'WRITE DOCS'],
         description: 'transform() shapes one entity and drops unexposed fields'
      );

      // @ Collection — mixed objects and row arrays
      $Entity = new class {
         public int $id = 2;
         public string $name = 'review pr';
      };
      $collected = $Resource->collect([
         ['id' => 1, 'name' => 'write docs'],
         $Entity
      ]);

      yield assert(
         assertion: $collected === [
            ['id' => 1, 'name' => 'WRITE DOCS'],
            ['id' => 2, 'name' => 'REVIEW PR']
         ],
         description: 'collect() transforms every entity in order'
      );

      // @ Empty collection
      yield assert(
         assertion: $Resource->collect([]) === [],
         description: 'collect() of an empty list is an empty list'
      );
   }
);
