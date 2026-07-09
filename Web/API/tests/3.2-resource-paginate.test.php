<?php

namespace Web\API;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should map pagination items while preserving the REST DX envelope',
   test: function () {
      // ! Fixture resource
      $Resource = new class extends Resource {
         public function transform (object|array $Entity): array
         {
            return ['id' => $Entity['id']];
         }
      };

      // @ Offset envelope — page/pages/limit/total preserved
      $body = $Resource->paginate([
         'items' => [
            ['id' => 1, 'secret' => 'x'],
            ['id' => 2, 'secret' => 'y']
         ],
         'page' => 1,
         'pages' => 7,
         'limit' => 2,
         'total' => 14
      ]);

      yield assert(
         assertion: $body === [
            'items' => [['id' => 1], ['id' => 2]],
            'page' => 1,
            'pages' => 7,
            'limit' => 2,
            'total' => 14
         ],
         description: 'Offset envelope: items mapped, page/pages/limit/total untouched'
      );

      // @ Cursor envelope — limit/next preserved
      $body = $Resource->paginate([
         'items' => [['id' => 3]],
         'limit' => 1,
         'next' => 'b64cursor'
      ]);

      yield assert(
         assertion: $body === [
            'items' => [['id' => 3]],
            'limit' => 1,
            'next' => 'b64cursor'
         ],
         description: 'Cursor envelope: items mapped, limit/next untouched'
      );

      // @ Body without items — returned unchanged
      $body = $Resource->paginate(['error' => 'no results']);

      yield assert(
         assertion: $body === ['error' => 'no results'],
         description: 'A body without items passes through unchanged'
      );
   }
);
