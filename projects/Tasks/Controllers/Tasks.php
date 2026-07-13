<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Tasks\Controllers;


use function trim;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Web\API\Problem;
use Web\App\Controller;

use Tasks\Models\Task;
use Tasks\Resources\Tasks as Resource;


class Tasks extends Controller
{
   public function list (Request $Request, Response $Response): Response
   {
      // @ Paginated through the Database resource (`?page` / `?limit` /
      //   `?cursor`) — X-Total-Count / Link headers are set by the core
      $body = $Response->Database->paginate(Task::class);

      // : Transformed items, untouched envelope
      return $Response->JSON->send(new Resource()->paginate($body));
   }

   public function show (Request $Request, Response $Response): Response
   {
      $task = $this->find($Response);

      return $Response->JSON->send(new Resource()->transform($task));
   }

   public function create (Request $Request, Response $Response): Response
   {
      $title = $this->validate($Request);

      $Response->Database->fetch(
         'INSERT INTO tasks (title) VALUES ($1)',
         [$title]
      );
      $Result = $Response->Database->fetch('SELECT id, title, done FROM tasks WHERE id = last_insert_rowid()');

      $Response->code(201);

      return $Response->JSON->send(new Resource()->transform($Result->row));
   }

   public function update (Request $Request, Response $Response): Response
   {
      $task = $this->find($Response);
      $title = trim((string) ($Request->fields['title'] ?? $task['title']));
      $done = (int) (bool) ($Request->fields['done'] ?? $task['done']);

      // ?
      if ($title === '') {
         throw new Problem(422, detail: 'The title must not be empty.');
      }

      $Response->Database->fetch(
         'UPDATE tasks SET title = $1, done = $2 WHERE id = $3',
         [$title, $done, $task['id']]
      );

      return $Response->JSON->send(new Resource()->transform([
         'id' => $task['id'],
         'title' => $title,
         'done' => $done
      ]));
   }

   public function delete (Request $Request, Response $Response): Response
   {
      $task = $this->find($Response);

      $Response->Database->fetch('DELETE FROM tasks WHERE id = $1', [$task['id']]);

      // : 204 No Content
      return $Response(code: 204);
   }

   // ---

   /**
    * Fetch the routed task row (`:id`) — 404 problem when it does not exist.
    *
    * @return array<string,mixed>
    */
   private function find (Response $Response): array
   {
      $id = (int) $this->Route->Params->id;
      $Result = $Response->Database->fetch(
         'SELECT id, title, done FROM tasks WHERE id = $1',
         [$id]
      );

      // ?
      if ($Result->empty === true) {
         throw new Problem(404, detail: "Task {$id} not found.");
      }

      // :
      return $Result->row;
   }

   /**
    * Validate the task payload — 422 problem on failure.
    */
   private function validate (Request $Request): string
   {
      $title = trim((string) ($Request->fields['title'] ?? ''));

      // ?
      if ($title === '') {
         throw new Problem(422, detail: 'The title must not be empty.');
      }

      // :
      return $title;
   }
}
