# Changelog

All changes are backward compatible unless stated. 145 tests (28 new) pass.

### Fixed
- **Sessions were never started.** `App` now calls `Session::start()` for every web request (cookie: HttpOnly, SameSite=Lax, Secure on HTTPS). Optional `SESSION_NAME` in `.env` renames the cookie.
- **`Auth::login()` always threw.** It called `User::where(...)->first()`, but `where()` returns an array. Now uses `User::firstWhere()`.
- **`Auth::logout()` threw** (a `User` object was interpolated into a string) and re-wrote the session just before destroying it.
- **`Session::make()` never overwrote**, so login error messages stuck on the first one and `login_attempts_*` / `last_attempt_*` counters in `Auth` could not be refreshed. `Auth` now uses the new `Session::set()`. `make()` keeps its "only if unset" meaning for existing callers.
- **`Session::push()`** was broken (recursed on an int). It now appends to an array value.
- **`Session::destroy()`** now empties `$_SESSION`, expires the cookie and destroys the stored session.
- **Session id is regenerated before the identity is stored** at login (session fixation).
- **`QueryBuilder::paginateTable()`** bound `LIMIT`/`OFFSET` as strings, which MySQL rejects with native prepares (the framework sets `ATTR_EMULATE_PREPARES=false`). They are inlined as ints. It also hard-coded `ORDER BY created_at`; a new `$orderBy` argument (validated) lifts that.
- **`Request::input()` on nested arrays** produced "Array to string" warnings. Fixed.
- **Log viewer** (`/system/logs`): had its auth middleware commented out, no `_log` view existed (every request 404'd), and delete was broken (it used `json()` + an md5-of-email check). Now it needs a signed-in `admin`/`superadmin`, ships `src/Views/_log.view.php`, and deletes via POST. `Logger::getLogs()` no longer warns when the log file does not exist.
- **`Middleware`**: the `admin` middleware was a stub that did nothing; it now requires `Auth::hasRole(['admin','superadmin'])` (403 otherwise). The `auth` middleware redirects to `AUTH_LOGIN_PATH` (default `/signin`).

### Added
- **CSRF protection** (`Tabel\Modules\Csrf`, helpers `csrf_field()` and `csrf_token()`). Opt in with `APP_CSRF=true`: the Router then rejects POST/PUT/PATCH/DELETE without the token (`_csrf` field or `X-CSRF-TOKEN` header; `Authorization: Bearer` requests are exempt). Off by default so existing apps keep working until their forms include `csrf_field()`.
- **`Request::raw($key)`** and `MainController::raw()`: trimmed input that is not HTML-escaped. Store raw, escape on output with `e()`. `input()` / `post()` still escape, for compatibility.
- **PUT / PATCH / DELETE routes**: `Router::put()`, `patch()`, `delete()`. A POST form can send `_method=PUT|PATCH|DELETE` (or an `X-HTTP-Method-Override` header).
- `Session::start()`, `set()`, `has()`, `pull()`, `regenerate()`.
- `auth()->role`.

### Not changed (worth knowing)
- `Request::input()` still HTML-escapes (see `raw()`).
- With `ATTR_EMULATE_PREPARES=false`, a named placeholder cannot be reused within one statement.
- `Router::resource()` still registers update/delete as POST routes.
- Models have no global scopes; multi-tenant apps need their own scoped base model.

### Upgrade notes
1. In `.env`: `APP_CSRF=true` (then put `<?= csrf_field() ?>` in every POST form and send `X-CSRF-TOKEN` from fetch calls), optionally `SESSION_NAME=yourapp`.
2. If your `public/index.php` already calls `session_start()`, you can remove it.
3. If you worked around `Auth::login()`, you can switch back to it.
