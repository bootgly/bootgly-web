<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Blog\Controllers;


use function trim;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use projects\Blog\Models\Post;
use Web\App\Controller;


class Posts extends Controller
{
   public function list (Request $Request, Response $Response): Response
   {
      // @ Paginated through the Database resource (`?page` / `?limit`) —
      //   items hydrate as Post models; X-Total-Count / Link headers are set
      $body = $Response->Database->paginate(Post::class);

      return $this->render('posts/list', [
         'posts' => $body['items'],
         'page' => $body['page'] ?? 1,
         'pages' => $body['pages'] ?? 1
      ]);
   }

   public function show (Request $Request, Response $Response): Response
   {
      // ?
      $post = $this->find($Response);
      if ($post === null) {
         $Response->View->render('errors/404');

         return $Response->code(404);
      }

      return $this->render('posts/show', [
         'post' => $post,
         'token' => $this->token($Request)
      ]);
   }

   public function create (Request $Request, Response $Response): Response
   {
      // ?: GET renders the blank form
      if ($Request->method === 'GET') {
         return $this->render('posts/create', ['token' => $this->token($Request)]);
      }

      // @ POST persists
      $title = trim((string) ($Request->fields['title'] ?? ''));
      $body = trim((string) ($Request->fields['body'] ?? ''));

      // ?
      if ($title === '' || $body === '') {
         $Request->Session->set('flash', 'Title and body are required.');

         return $this->redirect('/posts/create', 303);
      }

      $Response->Database->fetch(
         'INSERT INTO posts (title, body) VALUES ($1, $2)',
         [$title, $body]
      );
      $Request->Session->set('flash', 'Post created.');

      // : POST → 303 See Other
      return $this->redirect('/posts');
   }

   public function edit (Request $Request, Response $Response): Response
   {
      // ?
      $post = $this->find($Response);
      if ($post === null) {
         $Response->View->render('errors/404');

         return $Response->code(404);
      }

      return $this->render('posts/edit', [
         'post' => $post,
         'token' => $this->token($Request)
      ]);
   }

   public function update (Request $Request, Response $Response): Response
   {
      // ?
      $post = $this->find($Response);
      if ($post === null) {
         $Response->View->render('errors/404');

         return $Response->code(404);
      }

      $title = trim((string) ($Request->fields['title'] ?? ''));
      $body = trim((string) ($Request->fields['body'] ?? ''));

      // ?
      if ($title === '' || $body === '') {
         $Request->Session->set('flash', 'Title and body are required.');

         return $this->redirect("/posts/{$post['id']}/edit", 303);
      }

      $Response->Database->fetch(
         'UPDATE posts SET title = $1, body = $2 WHERE id = $3',
         [$title, $body, $post['id']]
      );
      $Request->Session->set('flash', 'Post updated.');

      return $this->redirect("/posts/{$post['id']}");
   }

   public function delete (Request $Request, Response $Response): Response
   {
      // ?
      $post = $this->find($Response);
      if ($post === null) {
         $Response->View->render('errors/404');

         return $Response->code(404);
      }

      $Response->Database->fetch('DELETE FROM posts WHERE id = $1', [$post['id']]);
      $Request->Session->set('flash', 'Post deleted.');

      return $this->redirect('/posts');
   }

   // ---

   /**
    * Fetch the routed post row (`:id`) or null when it does not exist.
    *
    * @return null|array<string,mixed>
    */
   private function find (Response $Response): null|array
   {
      $id = (int) $this->Route->Params->id;
      $Result = $Response->Database->fetch(
         'SELECT id, title, body, created_at FROM posts WHERE id = $1',
         [$id]
      );

      // :
      return $Result->empty ? null : $Result->row;
   }

   /**
    * Per-render masked CSRF token (BREACH mitigation) for form fields.
    */
   private function token (Request $Request): string
   {
      // :
      return CSRF::mask((string) $Request->Session->get('_csrf_token', ''));
   }
}
