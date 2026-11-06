# SPA

Single-page app demo: the same task list written twice — in **React** and in
**Vue**, both built by Vite — served by the Web platform's `Web\App\SPA`
handler on their own prefixes, over one JSON API backed by SQLite.

## Run

```bash
php bootgly project Demo/SPA start -f
```

The start builds what is missing: on the first run, and whenever a frontend's
sources changed since its last build, it runs `vite build` for that frontend
before the server forks — and `npm ci` first when `node_modules/` is missing or
older than `package-lock.json`. Node.js 22.22 or newer and npm must be on
the `PATH`; without them the start warns and serves on — the landing page and
the API work, and each frontend answers `503` until it is built.

The build is skipped on a `reload` (run `stop` and `start` to rebuild), on a
root launch (npm never runs as root here: build as your user) and with
`SPA_BUILD=0`.

Then open <http://localhost:8092>:

- <http://localhost:8092/react/> — the React build (React Router)
- <http://localhost:8092/vue/> — the Vue build (Vue Router)
- <http://localhost:8092/api/tasks> — the JSON API both of them call

Reload any page inside a build (`/react/about`, `/vue/tasks/2`): the server
answers the build's `index.html` and the client router renders the page — the
history-API fallback. A missing file with an extension (`/react/assets/x.js`)
answers `404`, never HTML.

- `PORT` — override the port (default `8092`).
- `SPA_BUILD=0` — skip the build on start.

## Build by hand

```bash
cd projects/Demo/SPA
npm install
npm run build          # both frontends → builds/react, builds/vue
```

## Develop with hot reload

Keep the project running (it serves the API), then start a Vite dev server —
it proxies `/api` to port 8092:

```bash
npm run dev:react      # http://localhost:5173/react/
npm run dev:vue        # http://localhost:5174/vue/
```

## API

| Method | Path | Body | Answer |
|---|---|---|---|
| `GET` | `/api/tasks` | — | `200 {"data": [task, …]}` |
| `GET` | `/api/tasks/:id` | — | `200 {"data": task}` |
| `POST` | `/api/tasks` | `{"title": "…"}` | `201 {"data": task}` |
| `PATCH` (or `PUT`) | `/api/tasks/:id` | `{"title"?: "…", "done"?: true}` | `200 {"data": task}` |
| `DELETE` | `/api/tasks/:id` | — | `204` |

A task is `{"id": 1, "title": "…", "done": false}`. Bodies are JSON objects
sent as `application/json`; `PATCH` and `PUT` both change only the fields they
carry. Errors are problem+json: `400` a body that is not a JSON object, `404` an
unknown task or API path, `415` another media type, `422` an invalid title or
`done`, or a full list (100 tasks).
The tasks live in `database/spa.sqlite` (the `Task` model), migrated and seeded on
the first start; delete the file to start over.
