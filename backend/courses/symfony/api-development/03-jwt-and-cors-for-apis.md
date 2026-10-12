---
slug: jwt-and-cors-for-apis
track: symfony
trackTitle: "Symfony"
trackOrder: 3
chapter: api-development
chapterTitle: "Chapter 11 — API Development"
chapterOrder: 11
title: "JWT and CORS for APIs"
difficulty: advanced
order: 3
estimatedMinutes: 13
tags: [symfony, api, jwt, cors, security]
---

## Learning Objective

Understand what a JWT is and why it lets an API stay stateless, and understand what CORS is and why a browser blocks cross-origin API calls unless the server explicitly allows them.

## Explanation

**Authentication without sessions: JWT.** In a traditional PHP web app, once a user logs in, the server stores "this session ID belongs to this user" in server-side session storage, and a cookie identifies which session belongs to the browser on every subsequent request. This works fine for a single server rendering HTML, but an API consumed by a separate Vue frontend (possibly served from a completely different domain, and potentially calling several independent backend services) has a problem: which server holds the session? Does every server need to share the same session storage?

A **JWT (JSON Web Token)** solves this by moving the "who is this" information *into the token itself*, cryptographically signed so it cannot be tampered with, instead of into server-side storage. When a user logs in, the server creates a JWT containing claims like the user's ID and an expiration time, signs it with a secret key only the server knows, and returns it to the client. From then on, the client sends that token with every request (typically in an `Authorization: Bearer <token>` header). The server verifies the signature — proving the token was genuinely issued by this server and has not been altered — and trusts the claims inside it, **without needing to look anything up in a database or session store**. This is what "stateless authentication" means: the server holds no per-user session state between requests; every request carries everything needed to authenticate it.

The trade-off: because the server does not store sessions, it cannot instantly "revoke" a single token before it naturally expires (unless you add extra infrastructure like a blocklist). This is why JWTs are typically given a short expiration time (e.g., 15–60 minutes), sometimes paired with a longer-lived "refresh token" used to obtain new access tokens without forcing the user to log in again.

**Why browsers block cross-origin requests: CORS.** Separately from authentication, there is a browser-level security mechanism you must understand when your Vue frontend (say, running on `http://localhost:5173`) calls your Symfony API (running on `http://localhost:8000`) — two different **origins** (a combination of scheme, domain, and port; if any of the three differ, it's a different origin). Browsers enforce the **Same-Origin Policy**: by default, JavaScript running on one origin is *not allowed* to read responses from a different origin, to stop a malicious website from silently making authenticated requests to, say, your bank's API using your logged-in session and reading the result.

**CORS (Cross-Origin Resource Sharing)** is the mechanism that lets a server *opt in* to being called from specific other origins. Before certain cross-origin requests, the browser automatically sends a "preflight" `OPTIONS` request asking the server, in effect, "will you allow a request from this origin, with this method and these headers?" The server answers with response headers like `Access-Control-Allow-Origin`, and only if the browser sees the calling origin permitted does it let the actual request's response reach your JavaScript code. Critically, **CORS is enforced by the browser, not by your server** — a non-browser client (like `curl` or another backend service) is never blocked by CORS at all; CORS only protects browser-based JavaScript from making unauthorized cross-origin reads on a user's behalf.

In this project, the `nelmio/nelmio-cors-bundle` package handles this configuration declaratively, so you do not have to manually set these headers on every response.

## Why It Matters

Nearly every modern application separates its frontend (a Vue, React, or mobile app) from its backend API, meaning they typically run on different origins during development and often in production too. Without correctly configured CORS, the frontend's requests will succeed at the network level but the browser will silently block the JavaScript from reading the response — a extremely common and confusing early bug for developers new to APIs, who see a successful request in the Network tab but an error in their JavaScript console. Meanwhile, JWTs are the backbone of how virtually all modern stateless APIs authenticate mobile apps, SPAs, and service-to-service calls, so misunderstanding how tokens are issued, sent, and expire leads directly to security bugs (like tokens leaking through URLs or logs) or broken user experiences (like being logged out unexpectedly).

## Example

A Vue app at `http://localhost:5173` sends `fetch('http://localhost:8000/api/tasks', { headers: { Authorization: 'Bearer eyJhbGci...' } })`. Two independent checks happen: first, the browser checks whether `http://localhost:8000` allows requests from `http://localhost:5173` (CORS) — if not, the JavaScript never even sees the response body. Second, assuming CORS allows it, the Symfony backend checks whether the JWT in the `Authorization` header is present, correctly signed, and not expired (authentication) — if not, it returns `401 Unauthorized`. Both checks must pass independently; one does not substitute for the other.

## Code

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/': null
```

## Code Explanation

- `nelmio_cors:` configures the NelmioCorsBundle, which intercepts requests and automatically adds the correct `Access-Control-*` response headers, and answers preflight `OPTIONS` requests, so you never write this logic by hand.
- `origin_regex: true` tells the bundle that `allow_origin` entries should be treated as regular expressions rather than exact strings, which is useful for allowing something like any `localhost` port during development.
- `allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']` reads the allowed origin(s) from an environment variable rather than hardcoding them, so production can allow only `https://app.example.com` while local development allows `http://localhost:5173`, without changing code.
- `allow_methods` lists which HTTP methods cross-origin requests may use; `OPTIONS` is included because the browser's own preflight request uses that method.
- `allow_headers: ['Content-Type', 'Authorization']` is essential for JWT-based auth: without explicitly allowing the `Authorization` header, the browser's preflight check would fail and the actual request carrying the JWT would never be sent.
- `paths: { '^/': null }` applies this CORS configuration to every path in the application (the regular expression `^/` matches everything), meaning both `/api/*` and any other route are covered.

## Common Mistakes

- Confusing CORS errors with authentication errors. A CORS failure appears in the browser console as a network-level block (often "No 'Access-Control-Allow-Origin' header"), while an auth failure is a normal HTTP `401` response the browser delivers to your JavaScript just fine. If you see a CORS error, the fix is server *CORS* configuration, not touching your JWT logic.
- Setting `allow_origin: ['*']` (allow everything) in production just to make an error go away. This defeats the purpose of CORS entirely and is especially dangerous combined with credentials/cookies; scope it to the specific origins that should legitimately call the API.
- Storing a JWT in `localStorage` and assuming it is safe from theft — it is readable by any JavaScript running on the page, making it vulnerable to XSS. Understanding this trade-off (versus an `HttpOnly` cookie) is part of using JWTs responsibly, even though implementing the alternative is beyond this lesson's scope.
- Forgetting that an expired JWT should produce a clean `401`, and not handling that case on the frontend — leading to a user who appears "randomly logged out" with a confusing generic error instead of being redirected to log in again.

## Best Practices

- Keep JWT expiration times short and pair them with a refresh mechanism, rather than issuing long-lived tokens "for convenience," which increases the damage window if a token is ever stolen.
- Never put sensitive data (passwords, full personal details) inside a JWT's claims — the payload is only *signed*, not encrypted, and can be read by anyone who intercepts it (it is just base64-encoded JSON).
- Configure CORS allowed origins from environment variables per environment, exactly as this project's `CORS_ALLOW_ORIGIN` does, instead of hardcoding a fixed list that has to be edited for every deployment target.
- Always explicitly allow the `Authorization` header in your CORS configuration if your API uses token-based auth — it is one of the most common "my request works in Postman but not in the browser" bugs.

## Exercise

A teammate reports: "In Postman, `GET /api/tasks` with my JWT works perfectly and returns my tasks. But when I call the exact same endpoint from our Vue app running on `http://localhost:5173`, the browser console shows a CORS error and no data is returned." Explain to them, in a few sentences, why the backend can behave differently for Postman versus the browser, and what configuration they should check first.

## Solution

Postman is not a browser, so it never enforces the Same-Origin Policy or CORS at all — it sends the request and shows you the raw response regardless of origin headers. A browser, however, enforces CORS for any page-based JavaScript making cross-origin requests, and will block the frontend's own code from reading the response unless the server's CORS configuration explicitly allows requests from `http://localhost:5173`. The teammate should check `config/packages/nelmio_cors.yaml` (or the `CORS_ALLOW_ORIGIN` environment variable it reads from) and confirm that `http://localhost:5173` — the Vue dev server's exact origin — is included in `allow_origin`. The JWT itself is likely fine, since the request never gets far enough for authentication to even run; CORS blocks it first.

## Mini Quiz

```yaml
- question: "What does 'stateless authentication' mean in the context of JWTs?"
  options:
    - "The server stores no per-user session data; each request carries everything needed to authenticate it inside the signed token"
    - "The user never needs to log in"
    - "The token never expires"
    - "The server keeps a database table of every issued token"
  answer: 0
  explanation: "Stateless means the server does not need to keep session state between requests — the signed token itself carries the claims needed to authenticate each request independently."

- question: "Which statement about CORS is correct?"
  options:
    - "CORS is enforced by the server's PHP code before any HTTP request is even sent"
    - "CORS is enforced by the browser, blocking JavaScript from reading cross-origin responses unless the server explicitly allows the calling origin"
    - "CORS prevents any HTTP request from ever reaching the server"
    - "CORS and JWT authentication are the same mechanism"
  answer: 1
  explanation: "CORS is a browser-level protection: the request often does reach the server, but the browser blocks the calling JavaScript from reading the response unless the server's CORS headers permit that origin."

- question: "Why is it important to explicitly include 'Authorization' in a CORS configuration's allow_headers when using JWTs?"
  options:
    - "It is not important; Authorization headers bypass CORS automatically"
    - "Without it, the browser's preflight check will not permit the actual request carrying the JWT to be sent with that header"
    - "It encrypts the JWT during transit"
    - "It is only needed for GET requests"
  answer: 1
  explanation: "The Authorization header must be explicitly allowed in CORS configuration, or the preflight check fails and the browser never sends the real request carrying the token."

- question: "Why are JWTs typically given short expiration times?"
  options:
    - "Short tokens are computationally cheaper to generate"
    - "To limit how long a stolen or leaked token remains usable, since the server cannot easily revoke it before it expires"
    - "Long tokens are not supported by the JSON format"
    - "Because browsers reject tokens longer than a few minutes old"
  answer: 1
  explanation: "Since a stateless server generally cannot revoke an individual JWT before its natural expiration, keeping the expiration window short limits the damage if a token is ever compromised."
```
