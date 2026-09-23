# REST endpoints — Web\API

JSON APIs on the Web platform: the `Web\API` routes mapper, problem+json errors and the stack without CSRF. The
rest of the build — boot, routes, controllers, Request and Response, data, tests — is the skill's `SKILL.md`.
Working reference: the `Demo/Tasks` example (REST and JWT). Pinned source: `<kit>/Web/Web/API/`.

- Stack without CSRF, with problem+json errors: `Configs(Middlewares: [new SecureHeaders, new RequestId, new BodyParser, new Problems])` — the first three from `Bootgly\WPI\Nodes\HTTP_Server_CLI\Router\Middlewares\`, `Problems` from `Web\API\`.
- `yield from Routes::map($Router, '/contacts', Contacts::class);` (`Web\API\Routes`) → `GET /contacts` list, `POST /contacts` create, `GET /contacts/:id` show, `PUT|PATCH /contacts/:id` update, `DELETE /contacts/:id` delete; same `only`/`except`/`middlewares` (a JWT `Authentication` on writes, as the `Demo/Tasks` example does).
- Errors: `throw new Problem(404, detail: "Contact {$id} not found.");` · `throw new Problem(422, detail: 'Invalid.', extensions: ['errors' => $Validation->errors]);` (`Web\API\Problem`).
- Created / no content: `$Response->code(201); return $Response->JSON->send($item);` · `return $Response(code: 204);`.

## Go deeper

- https://docs.bootgly.com/cookbook/web/contacts/overview.md (REST: `Routes::map`, `Problem`, `Resource`, pagination)
- https://docs.bootgly.com/manual/Web/API/overview.md
