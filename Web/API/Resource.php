<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Web\API;


use function is_array;
use function is_object;


/**
 * API resource transformer: shapes entities (ORM Models or row arrays)
 * into their public API representation.
 *
 * Collections keep the core REST DX envelope emitted by
 * `Response->Database->paginate()` (`items` + page/cursor keys,
 * `X-Total-Count`/`Link` headers) — `paginate()` only maps the items,
 * never recomputes the envelope.
 */
abstract class Resource
{
   /**
    * Transform one entity into its API shape.
    *
    * @param object|array<array-key,mixed> $Entity
    * @return array<string,mixed>
    */
   abstract public function transform (object|array $Entity): array;

   /**
    * Transform a list of entities.
    *
    * @param iterable<object|array<array-key,mixed>> $Entities
    * @return list<array<string,mixed>>
    */
   public function collect (iterable $Entities): array
   {
      // @
      $collected = [];
      foreach ($Entities as $Entity) {
         $collected[] = $this->transform($Entity);
      }

      // :
      return $collected;
   }

   /**
    * Transform the `items` of a pagination body in place — preserves the
    * REST DX keys (`page`/`pages`/`limit`/`total` or `limit`/`next`).
    *
    * @param array<string,mixed> $body Result of `$Response->Database->paginate()`.
    * @return array<string,mixed>
    */
   public function paginate (array $body): array
   {
      // ?
      if (isSet($body['items']) === false || is_array($body['items']) === false) {
         return $body;
      }

      // @ Entities only — the core envelope never carries scalars in `items`
      $collected = [];
      foreach ($body['items'] as $Entity) {
         if (is_object($Entity) === true || is_array($Entity) === true) {
            $collected[] = $this->transform($Entity);
         }
      }
      $body['items'] = $collected;

      // :
      return $body;
   }
}
