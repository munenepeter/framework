# Tabel

A Simple Framework - this is meant for simple projects where i do not need the all the parts offered by Laravel for instance, a static php website with a few features like reading and storing items in a database.

> Please note that this should only be used for simple php projects and i created this as part of teaching myself the internals of the Laravel framework.

> [!NOTE]
> Please, unless you are just buliding a very minimal and simple web-app that doesn't need all that laravel offers, i'd recommend for you to stick with laravel of symphony as this is very experimental and has not been tested, plus i just wanted to reduce the number of dependencies that comes with laravel

---

## Requirements

- PHP >= 8.2
- PDO extension (with `pdo_mysql` and/or `pdo_sqlite`)
- Composer

---

## Installation

Since there are no releases yet, require directly from the `main` branch:

```bash
composer require tabel/framework:dev-main
```

## Testing

Tests use PHPUnit with an in-memory SQLite database - no external services needed.

```bash
php vendor/phpunit/phpunit/phpunit --colors=always
```

Or via the Composer script:

```bash
composer test
```

Current coverage:

| Suite | File | Tests | What it covers |
|---|---|---|---|
| `QueryBuilderTest` | `tests/Database/QueryBuilderTest.php` | 22 | Core execute methods, all convenience helpers, raw SQL, operator validation |
| `ModelTest` | `tests/Database/ModelTest.php` | 27 | all(), find(), findOrFail(), where(), create(), update(), delete(), save(), count(), raw(), toArray(), magic accessors |
| `PaginationTest` | `tests/Database/PaginationTest.php` | 33 | QueryBuilder::paginateTable(), Model::paginate(), Model::paginateWhere(), LengthAwarePaginator metadata, Paginator in-memory slicing |
---

## Todo

### Features
- [ ] Default `users` table migration + Auth UI flow
- [ ] Mailing module (`Mail` class is stubbed, needs an adapter - SMTP/Mailgun/etc.)
- [ ] Relationship support on `Model` (`hasMany`, `belongsTo`) - currently use `raw()` + JOIN queries
- [ ] `is_unique` validation rule (needs DB access wired into `Validator`)
- [ ] `PATCH`/`PUT`/`DELETE` HTTP method support in the Router
- [ ] Middleware registration via `App::withMiddleware()` (currently a stub)
- [ ] Named routes and `route()` helper
- [ ] View templating / layout inheritance

### Tests
- [ ] `RouterTest` - registration, parameter extraction, callable routes, 404
- [ ] `RequestTest` - sanitisation, `only()`, `except()`, file upload
- [ ] `ValidatorTest` - each rule, custom error messages
- [ ] `SessionTest` - `make`, `get`, `unset`, `destroy`
- [ ] `AuthTest` - login, lockout, `check()`, `user()`, `hasRole()`
- [ ] `ConfigTest` - `.env` parsing, variable interpolation, cache invalidation
- [ ] `LoggerTest` - write, flush, delete
- [ ] `UploadTest` - MIME validation, size check, save path
---

## License

Be free, you can do anything with the code