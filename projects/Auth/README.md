# Auth — session/cookie authentication demo

The Bootgly auth scaffold: a complete session/cookie authentication flow built on
the core security stores (`Bootgly\API\Security\Users`, `Tokens`, `Tokens\Trust`)
and the WPI guards (`Authentication\Session` + `Authentication\Remember`).

## What it shows

- **Registration** with validation, argon2id hashing and auto sign-in
- **E-mail verification** — single-use selector/verifier link + resend
- **Login/logout** — session fixation defense (`Session->regenerate()`), uniform
  "Invalid credentials." failures, intended-URL redirect for guests
- **Remember-me** — rotating trusted-device cookie with theft detection and a
  fixed five-second grace window for the immediately previous validator
- **Password reset** — anti-enumeration forgot flow, single-use reset link,
  full invalidation (pending tokens + trusted devices die with the old password)
- **Change password** — current-password gate + other-devices sign-out
- **Per-route rate limits** on login, registration, forgot, reset and resend

## Run

```bash
php bootgly project Auth start        # http://localhost:8087
```

Seeded demo account: `demo@bootgly.com` / `bootgly-demo` (already verified).

## Mail

Three delivery lanes, picked by the `mail` config scope (`configs/mail/`):

1. **File sink (default)** — with `MAIL_HOST` empty, rendered messages land in
   `storage/mails/*.eml`. Zero setup: open the file, copy the link.
2. **Synchronous SMTP** — set `MAIL_HOST` (+ `MAIL_PORT`, `MAIL_SECURE`,
   `MAIL_USERNAME`, `MAIL_PASSWORD`).
3. **Queued** — additionally set `MAIL_QUEUE=1` and drain with
   `php bootgly queue run mail`.

## Configuration (`configs/auth/`)

| Env | Default | Meaning |
|-----|---------|---------|
| `APP_URL` | `http://localhost:8087` | Canonical base for e-mail links (never the Host header) |
| `AUTH_VERIFICATION_TTL` | `86400` | Verification link lifetime (seconds) |
| `AUTH_RECOVERY_TTL` | `3600` | Reset link lifetime (seconds) |
| `AUTH_REMEMBER_NAME` | `remember` | Remember-me cookie name |
| `AUTH_REMEMBER_TTL` | `2592000` | Remember-me lifetime (seconds) |

## Notes

- Cookies ship with `Secure; HttpOnly; SameSite=Lax` (framework-owned policy).
  Browsers treat `localhost` as a secure context, so the demo works over
  plain-HTTP localhost; behind a real domain, serve HTTPS.
- Registration reveals e-mail uniqueness inline (simple demo tradeoff); the
  forgot-password flow is strictly uniform.
- Unverified accounts can sign in — the account page shows a banner with a
  resend button. Completing a password reset also marks the e-mail verified
  (mailbox possession proven).
- An in-flight duplicate presenting the immediately previous remember
  validator within five seconds is declined without authentication, cookie
  clearing or device revocation. A later replay or any unrelated wrong
  validator revokes every trusted device and clears the presented cookie.
- Credential, action-token and remember-token verdicts are read from the
  primary database even when read replicas are configured.

## Upgrading the remember store

The current `trusts` schema adds two nullable columns: `previous VARCHAR(64)`
for the immediately previous validator digest and `rotated BIGINT` for its
epoch-second rotation time. Existing installations must run both additive
migrations before deploying the matching framework code; existing rows do not
need a backfill.

Apply and verify the migrations while old workers still run, then deploy and
restart or drain every worker as one cohort. Do not mix old and new workers:
old workers rotate `verifier` without refreshing `previous` and `rotated`. To
roll back, restore the old code and drain new workers first; the nullable
columns can remain in place or be removed afterward.
