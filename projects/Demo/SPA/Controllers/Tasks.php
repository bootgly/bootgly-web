<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Demo\SPA\Controllers;


use function array_key_exists;
use function explode;
use function is_bool;
use function is_string;
use function json_decode;
use function mb_strlen;
use function strtolower;
use function trim;
use stdClass;

use Bootgly\ADI\Databases\SQL\Builder\Auxiliaries\Orders;
use Bootgly\ADI\Databases\SQL\Builder\Identifier;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Demo\SPA\Models\Task;
use Web\API\Problem;
use Web\App\Controller;


/**
 * Tasks JSON API over the `Task` model — `{"data": …}` on success,
 * problem+json on failure.
 */
class Tasks extends Controller
{
   // * Config
   /** How many tasks the list keeps at most (the demo listens on every interface). */
   public const int LIMIT = 100;
   /** Longest title accepted, in characters. */
   public const int TITLE = 120;


   public function list (Request $Request, Response $Response): Response
   {
      $Tasks = $Response->Database->map(Task::class);
      $Selection = $Tasks->select()->order(Orders::Asc, new Identifier('id'));

      $Mapped = $Tasks->hydrate($Response->Database->await($Tasks->fetch($Selection)));

      // :
      return $Response->JSON->send(['data' => $Mapped->entities]);
   }

   public function show (Request $Request, Response $Response): Response
   {
      return $Response->JSON->send(['data' => $this->find($Response)]);
   }

   public function create (Request $Request, Response $Response): Response
   {
      $fields = $this->decode($Request);

      $Task = new Task;
      $Task->title = $this->validate($fields['title'] ?? null);

      // ? Full
      $Result = $Response->Database->fetch('SELECT COUNT(*) AS total FROM tasks');
      if ((int) ($Result->row['total'] ?? 0) >= self::LIMIT) {
         $limit = self::LIMIT;

         throw new Problem(422, detail: "The demo keeps at most {$limit} tasks — delete one first.");
      }

      // @ INSERT — hydrating the save writes the generated id into the entity
      $Tasks = $Response->Database->map(Task::class);
      $Mapped = $Tasks->hydrate($Response->Database->await($Tasks->save($Task)));

      $Response->code(201);

      // :
      return $Response->JSON->send(['data' => $Mapped->entity ?? $Task]);
   }

   public function update (Request $Request, Response $Response): Response
   {
      $fields = $this->decode($Request);
      $Task = $this->find($Response);

      // ! Each field is optional; a present one must be valid
      if (array_key_exists('title', $fields) === true) {
         $Task->title = $this->validate($fields['title']);
      }
      if (array_key_exists('done', $fields) === true) {
         // ?
         if (is_bool($fields['done']) === false) {
            throw new Problem(422, detail: 'The `done` field must be true or false.');
         }

         $Task->done = $fields['done'];
      }

      // @ UPDATE — the key is set
      $Response->Database->await($Response->Database->map(Task::class)->save($Task));

      // :
      return $Response->JSON->send(['data' => $Task]);
   }

   public function delete (Request $Request, Response $Response): Response
   {
      $Task = $this->find($Response);

      $Response->Database->await($Response->Database->map(Task::class)->delete($Task));

      // : 204 No Content
      return $Response(code: 204);
   }

   // ---

   /**
    * Decode the request body — a JSON object, sent as `application/json`.
    *
    * Any other media type is a `415` problem: a form-encoded or plain-text
    * POST is a CORS "simple request" that any site may send from a visitor's
    * browser, while `application/json` needs a preflight this API never
    * grants. A body that is not a JSON object is a `400` problem.
    *
    * @return array<string,mixed>
    */
   private function decode (Request $Request): array
   {
      // ? Media type — the part before any `;` parameter
      $type = strtolower(trim(explode(';', $Request->Header->get('Content-Type') ?? '', 2)[0]));
      if ($type !== 'application/json') {
         throw new Problem(415, detail: 'Send the task as application/json.');
      }

      // ? A JSON object — malformed JSON, a list or a scalar is not one
      $Body = json_decode($Request->input);
      if ($Body instanceof stdClass === false) {
         throw new Problem(400, detail: 'The body must be a JSON object.');
      }

      // :
      return (array) $Body;
   }

   /**
    * Find the routed task (`:id`) — a 404 problem when it does not exist.
    */
   private function find (Response $Response): Task
   {
      $id = (int) $this->Route->Params->id;
      $Tasks = $Response->Database->map(Task::class);

      /** @var null|Task $Task */
      $Task = $Tasks->hydrate($Response->Database->await($Tasks->find($id)))->entity;

      // ?
      if ($Task === null) {
         throw new Problem(404, detail: "Task {$id} not found.");
      }

      // :
      return $Task;
   }

   /**
    * Validate a title — a 422 problem when it is not a non-empty string of
    * at most `TITLE` characters.
    */
   private function validate (mixed $title): string
   {
      $title = is_string($title) ? trim($title) : '';

      // ?
      if ($title === '') {
         throw new Problem(422, detail: 'The title must not be empty.');
      }
      if (mb_strlen($title) > self::TITLE) {
         $limit = self::TITLE;

         throw new Problem(422, detail: "The title must have at most {$limit} characters.");
      }

      // :
      return $title;
   }
}
