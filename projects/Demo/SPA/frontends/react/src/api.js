// Minimal JSON client for the Bootgly tasks API (same origin, absolute paths;
// proxied to the Bootgly project by Vite in `npm run dev`).
// frontends/react and frontends/vue keep this file identical.
async function request (method, path, body) {
  const init = { method, headers: { Accept: 'application/json, application/problem+json' } };
  if (body !== undefined) {
    init.headers['Content-Type'] = 'application/json';
    init.body = JSON.stringify(body);
  }

  const response = await fetch(path, init);
  // 204 No Content (DELETE): there is no body to parse.
  if (response.status === 204) return null;

  let payload = null;
  try {
    payload = await response.json();
  } catch {
    // Non-JSON body (e.g. a proxy error page): fall through to the status text.
  }

  if (!response.ok) {
    // RFC 9457 problem+json: prefer `detail`, then `title`.
    const message = payload?.detail || payload?.title || response.statusText || `HTTP ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    throw error;
  }

  return payload?.data ?? null;
}

export const api = {
  list: () => request('GET', '/api/tasks'),
  get: (id) => request('GET', `/api/tasks/${encodeURIComponent(id)}`),
  create: (title) => request('POST', '/api/tasks', { title }),
  update: (id, changes) => request('PATCH', `/api/tasks/${encodeURIComponent(id)}`, changes),
  remove: (id) => request('DELETE', `/api/tasks/${encodeURIComponent(id)}`),
};
