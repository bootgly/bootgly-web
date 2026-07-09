# Chat

Realtime chat demo over the Bootgly WebSocket server (`WS_Server_CLI` + Channels).

## Run

```bash
./bootgly project Chat start -f
```

Then open <http://localhost:8085> in two browser tabs, click **Connect** and
chat — the WebSocket server itself serves the client page on the same port
(plain HTTP requests fall back to `statics/chat.html`).

Every connection joins `#lobby`; send `/join <room>` to switch rooms —
messages relay only inside the current room.

- `PORT` — override the port (default `8085`).

> `statics/chat.html` also works straight from disk (`file://`) — it points
> itself at `ws://localhost:8085` when not served over HTTP.
