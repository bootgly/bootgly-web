---
name: bootgly-build-web
description: "Build Web platform features in a WPI project of a bootgly.kit, on the Web\\App shell (MVC) and Web\\API (REST) of the Web package: boot a Web\\App, route sets, controllers mapped with Controllers::map() and their fixed action names, Request and Response, views and layouts, static files, sessions, CSRF-protected forms, redirects, problem+json REST endpoints with Routes::map() and Problem, the $Response->Database a Web\\App mounts, and HTTP tests. Extends bootgly-build. Use when adding or changing a page, endpoint, route, controller, view, layout, form or REST resource in a projects/<Name>/ WPI project, when switching the WPI scaffold to Web\\App, when copying a Web example (Demo/Blog, Demo/Site, Demo/Tasks, Demo/Auth, Demo/Chat), or when a route answers an unexpected 404 or 403. Tables, migrations and models: bootgly-build (references/database.md). Creating or running the project: bootgly-project. Tests: bootgly-test. Not for changing the Web package itself."
---
<!-- Machine-managed by `bootgly kit boot`: it rewrites this skill, and removes it once no longer shipped. Do not edit. -->

# Bootgly build: the Web platform

Commands are written `bootgly …`. Without the global wrapper, run `php ../bootgly …` from `projects/` and `php ../../bootgly …` from `projects/<Name>/` (one more `../` per nested segment).

The rules live in `<kit>/projects/.agents/rules/`; this skill links them as `../../../.agents/rules/<Section>.md`.

This skill extends [bootgly-build](../bootgly-build/SKILL.md): its §1, §3 and Definition of done apply unchanged. The
Web platform package (`<kit>/Web/`) ships it; `kit boot` lays it down while that package is set up in the kit.

**Shipped examples** (imported into `projects/Demo/`, originals in `<kit>/Web/projects/Demo/`): `Blog` (MVC CRUD
and CSRF forms), `Site` (pages via `Action`), `Tasks` (REST and JWT), `Auth` (sessions, guards, HTTP E2E tests),
`Chat` (WebSocket). Copy one (`bootgly projects create MyBlog --from=Demo/Blog --yes`) and rename its namespace first.

## 1. Know what comes from where

- Sessions (`$Request->Session`), the router and the `CSRF` middleware are core WPI. Only `Controllers`/`Controller`, `Statics`, the default middleware stack (with `CSRF`) and the default layout come from the Web platform's `Web\App` shell (`Web\API` adds `Action`, `Routes`, `Problem`). The scaffold boots a bare `HTTP_Server_CLI` — enough for closure routes; switch its boot to `Web\App` (step 2) for controllers and views.
- Pinned source: `<kit>/Web/Web/App/`, `<kit>/Web/Web/API/`, `<kit>/Bootgly/Bootgly/WPI/Nodes/HTTP_Server_CLI/`.

## 2. Boot a `Web\App` — `<Name>.Project.php`

This is the whole file; `description`, `version` and `author` are optional — carry them over from the scaffold:

```php
<?php

use Bootgly\API\Endpoints\Server\Modes;
use Bootgly\API\Projects\Project;
use Web\App;
use Web\App\Configs;


return new Project(
   name: 'Journal',
   exportable: true,
   boot: function (array $arguments = [], array $options = []): void
   {
      $App = new App(Mode: match (true) {
         isset($options['f']) => Modes::Foreground,
         isset($options['i']) => Modes::Interactive,
         isset($options['m']) => Modes::Monitor,
         default => Modes::Daemon
      });
      $App
         // ! Keep the PORT override — the final check starts a throwaway instance with it
         ->configure(new Configs(port: getenv('PORT') ? (int) getenv('PORT') : 8080, workers: 1))
         ->load(__DIR__ . '/router')
         ->start();
   }
);
```

Default stack: `SecureHeaders`, `RequestId`, `BodyParser`, `CSRF`; `Configs(Middlewares: [...])` replaces it wholesale (named arguments only). A `configs/database/` folder makes `$Response->Database` available (§8).

## 3. Register routes

`router/router.index.php` returns the set names (`return ['Journal'];`); each name is `router/routes/<Name>.routes.php`. A set missing from the list never loads.

```php
<?php

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router;
use Journal\Controllers\Notes;
use Web\API\Action;
use Web\App\Controllers;
use Web\App\Statics;


return static function (Request $Request, Response $Response, Router $Router): Generator
{
   // # Resource — GET /notes, GET /notes/create + POST /notes, GET /notes/:id
   yield from Controllers::map($Router, '/notes', Notes::class, only: ['list', 'create', 'show']);
   // # Extra action — any single-verb public method, dispatched lazily
   yield $Router->route('/notes/:id<int>/pin', new Action(Notes::class, 'pin'), POST);
   // # Closure — non-static, so $this is the matched Route
   yield $Router->route('/hello/:name<alpha>', function (Request $Request, Response $Response) {
      return $Response(body: "Hello, {$this->Params->name}!");
   }, GET);
   yield $Router->route('/statics/:file*', new Statics, GET);
   // # Fallback — keep it last (a page instead: create views/errors/404.template.php and render 'errors/404')
   yield $Router->route('/*', function (Request $Request, Response $Response) {
      return $Response(code: 404, body: 'Not Found');
   }, GET);
};
```

- `route(string $route, callable $handler, null|string|array $methods = null, array $middlewares = [], null|array $cache = null)`. Methods are constants — `GET`, `POST`, `[POST, PUT]`; `null` means any.
- Patterns: `:id`, `:id<int|alpha|alphanum|slug|uuid>`, `:oid(\d+)`, `:file*` (rest of the path, last segment only), `/*` (catch-all), `/admin/:*` with nested `yield $Router->route('users', …)` (group). A failed constraint falls through to the next route.
- Params are strings: `$this->Params->id` in a closure, `$this->Route->Params->id` in a controller — cast them.
- Routes register on each worker's first request: after editing routes or classes, `bootgly project <Name> reload`.

## 4. Controllers

`Controllers::map($Router, $path, Class::class, only:, except:, middlewares:, constraint: 'int')` expands to the
routes below. The action names are fixed — **MUST**: `list`, `show`, `create`, `edit`, `update`, `delete`
([Naming_conventions.md](../../../.agents/rules/Naming_conventions.md) › Methods); the mapper looks for exactly those.

| Route | Methods | Action |
|---|---|---|
| `/path` | GET | `list` |
| `/path/create` · `/path` | GET · POST | `create` (branch on `$Request->method`) |
| `/path/:id` · `/path/:id/edit` | GET | `show` · `edit` |
| `/path/:id` | POST, PUT, PATCH | `update` |
| `/path/:id/delete` · `/path/:id` | POST · DELETE | `delete` |

```php
<?php

namespace Journal\Controllers;


use function trim;

use Bootgly\WPI\Nodes\HTTP_Server_CLI\Request;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Response;
use Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\CSRF;
use Web\App\Controller;


class Notes extends Controller
{
   // … list (), show (), pin () — every routed action must exist (Action throws otherwise)

   public function create (Request $Request, Response $Response): Response
   {
      // ?: GET renders the blank form
      if ($Request->method === 'GET') {
         return $this->render('notes/create', [
            'flash' => $Request->Session->pull('flash'),
            // ! Masked per render (BREACH mitigation)
            'token' => CSRF::mask((string) $Request->Session->get('_csrf_token', ''))
         ]);
      }

      // @ POST — validate, hand off to a model or service, flash, redirect
      $title = trim((string) ($Request->fields['title'] ?? ''));
      if ($title === '') {
         $Request->Session->set('flash', 'A title is required.');
         return $this->redirect('/notes/create', 303);
      }
      $Request->Session->set('flash', 'Note saved.');

      // : POST → 303 See Other
      return $this->redirect('/notes', 303);
   }
}
```

A fresh controller is built per request — keep no state on it. Helpers: `render(string $view, null|array $data = null, null|string|false $layout = null)` and `redirect(string $URI, null|int $code = null)`.

## 5. Request and Response

| Need | Code |
|---|---|
| Method, target | `$Request->method`, `$Request->URI` |
| Query value | `$Request->query('q', '')` (string) · all: `$Request->queries` |
| Body fields | `$Request->fields['name'] ?? ''` — form, multipart or JSON; no or unknown `Content-Type` gives `[]` |
| Raw body, header | `$Request->input`, `$Request->Header->get('Accept')` |
| Plain, status | `return $Response(code: 404, body: 'Not Found');` · `$Response->code(201);` |
| JSON | `return $Response->JSON->send(['id' => 1]);` |
| Header | `$Response->Header->set('Cache-Control', 'no-store');` |
| Redirect | `$Response->redirect('/path', 303)` — only `/`-relative targets (others become `/` unless `allowExternal: true`); default 303 after POST, 307 otherwise |
| Session | `$Request->Session->get/set/pull/forget/check(...)` |

## 6. Views, layout, statics

- `views/<dir>/<name>.template.php`, rendered as `'<dir>/<name>'`; data keys become variables, `$Route` is always set. A missing view — or a name outside `[A-Za-z0-9_/-]` — answers **403**.
- `Web\App` wraps every view in `views/layouts/main.template.php`: create it with `@yield content;` where the page goes, or render with `layout: false`.
- Escape every value: `<?= htmlspecialchars((string) $title) ?>` (or `@>> $title;`). Partials: `@include partials/flash;`.
- Forms carry `<input type="hidden" name="_token" value="<?= htmlspecialchars((string) $token) ?>">`. A POST/PUT/PATCH/DELETE without a valid `_token` field (or `X-CSRF-Token` header) gets **403**.
- Assets live in `statics/`, linked as `/statics/app.css` and served by `new Statics` with the right media type.

## 7. REST endpoints

A JSON API — `Web\API\Routes::map()`, problem+json errors, a stack without CSRF: [references/REST.md](references/REST.md).

## 8. Data: `$Response->Database`

With a `configs/database/` scope ([database.md](../bootgly-build/references/database.md) §1), `Web\App` mounts the `Database`
response resource by itself (`configs/kv/` mounts `KV`); a `Resources:` entry in `Configs` wins. Query and transact
through `$Response->Database` as its §5 shows; to migrate and seed at start, build `$Database` in `boot` (its §2).

## 9. Test it

List a case in a suite registered in `tests/autoboot.php` ([Testing guidelines](../../../.agents/rules/Testing_guidelines.md); the bootgly-test skill runs the loop). Test the logic behind the routes directly, and that `router/router.index.php` lists the set and `router/routes/<Name>.routes.php` returns a `Closure` — the Guestbook cookbook's `1.2-router.Test.php` does both. Full HTTP tests return a `Bootgly\WPI\Nodes\HTTP_Server_CLI\Tests\Suite\Test`: copy the suite in `projects/Demo/Auth/tests/E2E/` (original: `<kit>/Web/projects/Demo/Auth/tests/E2E/`).

## Checks

On top of the Definition of done in [bootgly-build](../bootgly-build/SKILL.md):
- [ ] In its step 4, `curl -i` every route you changed; a form POST needs a cookie jar and the page's `_token`.

## Go deeper

- Cookbooks: https://docs.bootgly.com/cookbook/web/guestbook/overview.md (MVC: controller, views, CSRF, flash, tests) · https://docs.bootgly.com/cookbook/web/contacts/overview.md (REST: `Routes::map`, `Problem`, `Resource`, pagination) · https://docs.bootgly.com/cookbook/web/shop/overview.md · https://docs.bootgly.com/cookbook/web/polls/overview.md (session cart, `Action` routes beside a resource)
- Web platform: https://docs.bootgly.com/guide/web-platform/overview.md · https://docs.bootgly.com/manual/Web/App/overview.md · https://docs.bootgly.com/manual/Web/API/overview.md
- Core WPI: https://docs.bootgly.com/manual/WPI/HTTP/HTTP_Server_CLI/Router/overview.md (params, groups, `intercept()`, response cache) · https://docs.bootgly.com/manual/WPI/HTTP/HTTP_Server_CLI/Request/overview.md · https://docs.bootgly.com/manual/WPI/HTTP/HTTP_Server_CLI/Response/overview.md · https://docs.bootgly.com/manual/WPI/HTTP/HTTP_Server_CLI/Middlewares/overview.md (CORS, RateLimit, Authentication, ETag)
- https://docs.bootgly.com/guide/views/overview.md · https://docs.bootgly.com/guide/templates/overview.md · https://docs.bootgly.com/guide/reload/overview.md · https://docs.bootgly.com/testing/about/testing/overview.md
