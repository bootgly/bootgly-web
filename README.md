<p align="center">
  <img src="https://github.com/bootgly/.github/raw/main/bootgly-logo.128x128.jpg" alt="bootgly-logo" width="120px" height="120px"/>
</p>
<h1 align="center">Bootgly Web</h1>
<p align="center">
  <i>Bootgly Web Platform.</i>
</p>
<p align="center">
  <a href="https://packagist.org/packages/bootgly/bootgly-web">
    <img alt="Bootgly License" src="https://img.shields.io/github/license/bootgly/bootgly-web"/>
    <!--
    </br>
    <img alt="Github Actions - Bootgly Workflow" src="https://img.shields.io/github/actions/workflow/status/bootgly/bootgly/bootgly.yml?label=bootgly"/>
    <img alt="Github Actions - Docker Workflow" src="https://img.shields.io/github/actions/workflow/status/bootgly/bootgly/docker.yml?label=docker"/>-->
  </a>
</p>

> Bootgly Web Platform composed by the WPI interface.

The **opinionated web layer** over `Bootgly\WPI`: WPI stays deliberately low-level; this platform is where the opinions live — controllers, resource routing, problem+json errors, static assets and view conventions. Everything it wires remains plain WPI underneath.

## Getting started

Use the **canonical installer** — it sets up a [bootgly.kit](https://github.com/bootgly/bootgly.kit) workspace, where the platforms are unified. Every platform (Console and **Web**) comes set up, with no question asked:

```bash
curl -fsSL https://bootgly.com/install | bash
```

The wizard's first run imports every demo project of this platform into `projects/Demo/`, as a living guide:

```bash
php bootgly projects list
```

> ⚠️ Using this repository directly is **discouraged** — `bootgly.kit` is the starting point: it is where the Bootgly core and the platforms are mounted and booted together. See [Getting started](https://docs.bootgly.com/guide/getting-started). Cloning `bootgly-web` standalone is only meant for developing the platform itself.

## Modules

- **`Web\App`** — the MVC application shell: an opinionated boot of the canonical HTTP server with a default middleware stack (SecureHeaders, RequestId, BodyParser, CSRF), `Controller` base + lazy dispatch (fresh instance per request), `Controllers::map()` resource routing (HTML-form-aware), `Statics` (inline assets with the right media type), `SPA` (single-page-app handler with history-API fallback), `Files` (jailed, typed and cached file resolution behind `Statics` and `SPA`) and `Views` conventions (default layout + shared exports).
- **`Web\API`** — the REST shell: `Action` (invokable controller-action dispatcher), `Routes::map()` REST resource routing, `Problem`/`Problems` (RFC 9457 problem details as throwable + middleware error boundary) and `Resource` transformers (with core pagination envelope reuse).

## Demo projects (exportable)

| Project | Port | Shows |
|---------|------|-------|
| `Auth`  | 8087 | Session/cookie authentication: registration, e-mail verification, login + remember-me, password reset/change, per-route rate limits, SQLite (zero setup) |
| `Blog`  | 8080 | Full MVC loop: controllers, ORM models, views, Session flash + masked CSRF forms, SQLite (zero setup) |
| `Chat`  | 8085 | Realtime rooms over the WebSocket server — the client page is served on the same port |
| `Site`  | 8088 | Landing pages: controller-dispatched views, layouts, inline statics, no database |
| `SPA`   | 8092 | Single-page apps: React and Vue Vite builds served with history-API fallback over a JSON API, SQLite (zero setup) |
| `Tasks` | 8090 | REST API: resources, problem+json, JWT-protected mutations, pagination (`X-Total-Count`/`Link`) |

Once imported in the kit:

```bash
php bootgly project Demo/Blog start
```

## Developing the platform

Only for working on `bootgly-web` itself (with the `bootgly` core as a sibling checkout):

```bash
./bootgly test                                # test suites
vendor/bin/phpstan analyse -c @/phpstan.neon  # static analysis
./bootgly project Demo/Blog start -f          # run a demo in foreground
```

[Documentation][PROJECT_DOCS] — see the *Web Platform* guide and the *Web* manual pages.



<!-- Links -->
[PROJECT_DOCS]: https://docs.bootgly.com/
[GITHUB_MAIN_REPOSITORY]: https://github.com/bootgly/bootgly/
[GITHUB_ORG_SPONSOR]: https://github.com/sponsors/bootgly/
