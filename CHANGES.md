# Changes (desktop shell work)

Everything here was found or needed while building the Tabel desktop. Each
fix has a test in `tests/Core/`; the two Router ones and the Logger one were
confirmed to fail against the old behaviour. 82 tests became 117.

## Bugs fixed

| Where | Problem | Effect |
|---|---|---|
| `Modules/Logger.php` | Checked whether the log **file** existed, then `mkdir`'d the **folder**. | On a fresh checkout (folder present, no log yet) `mkdir` warned "File exists" and the error handler turned it into a **500 on the first request**. |
| `Core/Router.php` | `{param}` compiled to `(.+)`, which matches `/`. | `/apps/{app}` swallowed `/apps/books/invoices`; a more specific route registered later could never match. Now `([^/]+)`. Hand-written regex in a route is untouched. |
| `Core/Router.php` | `resource()` built the prefixed URI, then `get()`/`post()` prefixed it again. | `Router::group(['prefix'=>'admin'], ...)` + `resource()` gave `admin/admin/posts`. |
| `Core/Mail.php` | Namespace `Tabel\Core\Mantle` in `Core/`. | Composer skipped the class (PSR-4 mismatch). |
| `Core/Config.php` | `.env` values kept their quotes; `# comments` were parsed as keys. | `config('app.name')` returned `"Tabel"` with quotes; `# DB_HOST=x` created a bogus `# db` section. Config cache key bumped to `config_cache_v2` so old caches are not reused. |
| `Core/App.php` | `APP_TIMEZONE` was parsed and never applied. | `date()` always ran in UTC. |
| `helpers.php` | `app()`, `logger()`, `auth()` were not wrapped in `function_exists`. | The shipped `tests/bootstrap.php` stubs them, so the suite could not even start ("Cannot redeclare app()"). |
| `helpers.php` | `view()` used `require_once`. | A view rendered twice in a request (partial in a loop, fragment after a page) printed nothing the second time. |
| `helpers.php` | `asset()` built `BASE_URL:PORT/path` from `SERVER_NAME`. | Broke behind proxies, in Docker, on non-default ports. Now root-relative and fingerprinted. |
| `helpers.php` | `is_dev()` returned `null` for any env other than development/production. | Now a real `bool`. |

## Added

* `App`: **lazy `pdo` / `db`**. The connection opens on first `app()->get('pdo')`, so a page that never touches the database no longer fails when the database is down or unconfigured. (Verified: default MySQL `.env`, no MySQL running, desktop returns 200.)
* `Request::isHtmx()`, `isBoosted()`, `htmxTarget()` (the `HX-*` headers), `query()` (raw `$_GET`, unlike `input()` which HTML-escapes), `isSameOrigin()` (Sec-Fetch-Site, then Origin vs Host: a cheap CSRF guard for state-changing requests).
* Helpers: `partial()` (echo a view in its own scope, repeatable), `render()` (same, returns a string), `e()` (HTML escape), `asset_url()` (returns `/css/x.css?v=mtime`), `absolute_url()` (uses `APP_URL`; sanitises the Host header otherwise).

## Not fixed (out of scope, worth knowing)

* `session_start()` is never called, so `Session` and `Auth` cannot persist across requests.
* `Auth` calls `->first()` on `Model::where()`, which returns an array.
* `SystemController::deleteLogs()` calls `$this->json()` (which exits) before the code that deletes; `/system/logs` renders a `_log` view that does not exist; and the routes have no auth middleware. Add auth before anyone creates that view, or the log file becomes public.
* `tabelErrorHandler` turns every warning and deprecation into a 500. Fine in development; consider logging instead in production.
