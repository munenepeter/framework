<?php

namespace Tabel\Database;

use Tabel\Modules\LengthAwarePaginator;
use Tabel\Modules\Paginator;

/**
 * Model
 *
 * Base class for all application models. Subclasses only need to set
 * $table (and optionally $primaryKey / $timestamps).
 *
 * Eloquent-style API surface:
 *   Model::all()
 *   Model::find($id)
 *   Model::findOrFail($id)
 *   Model::where($column, $value, $operator = '=')
 *   Model::firstWhere($column, $value, $operator = '=')
 *   Model::paginate(int $perPage)
 *   Model::create($data)
 *   Model::raw($sql, $bindings)
 *   Model::count($column, $value)
 *
 * Instance methods (called on a retrieved model object):
 *   $model->update($data)
 *   $model->delete()
 *   $model->save()        - insert if new, update if loaded from DB
 *   $model->toArray()
 *
 * Usage example:
 *
 *   class User extends Model {
 *       protected static string $table = 'users';
 *   }
 *
 *   $users   = User::all();
 *   $user    = User::find(1);
 *   $admins  = User::where('role', 'admin');
 *   $newUser = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
 *
 * @package Tabel\Database
 */
abstract class Model {

    /** Table name - override in every subclass. */
    protected static string $table = '';

    /** Primary key column name. */
    protected static string $primaryKey = 'id';

    /** Whether to auto-manage created_at / updated_at. */
    protected static bool $timestamps = true;

    /**
     * Columns that may be mass-assigned via create() / fill().
     * Leave empty to allow all columns (be careful).
     *
     * @var string[]
     */
    protected static array $fillable = [];

    /**
     * Columns excluded from toArray() / JSON output.
     *
     * @var string[]
     */
    protected static array $hidden = [];

    // Internal state

    /** Raw attribute bag. */
    private array $attributes = [];

    /** Original values as loaded from DB - used to detect dirty state. */
    private array $original = [];

    /** True when this instance was loaded from the database. */
    private bool $exists = false;

    // -------------------------------------------------------------------------
    // QueryBuilder access
    // -------------------------------------------------------------------------

    protected static function qb(): QueryBuilder {
        // The App container stores the PDO under 'pdo'.
        if (function_exists('app')) {
            $pdo = app()->get('pdo');
            if ($pdo instanceof \PDO) {
                return QueryBuilder::getInstance($pdo);
            }
        }
        throw new \RuntimeException(
            'No PDO connection available. Make sure App::boot() has run.'
        );
    }

    private static function tableName(): string {
        if (static::$table !== '') {
            return static::$table;
        }
        // Auto-derive: "App\Models\UserProfile" -> "user_profiles"
        $base = basename(str_replace('\\', '/', static::class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $base)) . 's';
    }

    /**
     * Return all rows as an array of static model instances.
     *
     * @return static[]
     */
    public static function all(): array {
        $rows = static::qb()->selectAll(static::tableName());
        return array_map(fn($row) => static::fromRow((array) $row), $rows);
    }

    /**
     * Find a single row by primary key. Returns null when not found.
     *
     * @param  mixed $id
     * @return static|null
     */
    public static function find(mixed $id): ?static {
        $row = static::qb()->selectById(static::tableName(), $id, static::$primaryKey);
        return $row ? static::fromRow((array) $row) : null;
    }

    /**
     * Find a single row or throw an exception.
     *
     * @param  mixed $id
     * @return static
     * @throws \RuntimeException
     */
    public static function findOrFail(mixed $id): static {
        $model = static::find($id);
        if ($model === null) {
            throw new \RuntimeException(
                static::class . " with " . static::$primaryKey . " = {$id} not found."
            );
        }
        return $model;
    }

    /**
     * Return all rows matching a column condition.
     *
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator  =, <, >, <=, >=, !=, LIKE
     * @return static[]
     */
    public static function where(string $column, mixed $value, string $operator = '='): array {
        $rows = static::qb()->selectWhere(static::tableName(), $column, $value, $operator);
        return array_map(fn($row) => static::fromRow((array) $row), $rows);
    }

    /**
     * Return the first row matching a column condition, or null.
     *
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return static|null
     */
    public static function firstWhere(string $column, mixed $value, string $operator = '='): ?static {
        $rows = static::where($column, $value, $operator);
        return $rows[0] ?? null;
    }

    /**
     * Insert a new row and return the hydrated model.
     *
     * @param  array $data
     * @return static
     */
    public static function create(array $data): static {
        $data = static::filterFillable($data);
        $data = static::withTimestamps($data, true);

        $id = static::qb()->insertRow(static::tableName(), $data);

        // Re-fetch so we get DB defaults (auto-increment id, default values, etc.)
        $pk = static::$primaryKey;
        $data[$pk] = $id;

        return static::fromRow($data, true);
    }

    /**
     * Run an arbitrary raw SQL statement.
     * SELECT -> returns array of stdClass objects.
     * Other verbs -> returns affected row count.
     *
     * @param  string $sql
     * @param  array  $bindings
     * @return array|int
     */
    public static function raw(string $sql, array $bindings = []): array|int {
        return static::qb()->raw($sql, $bindings);
    }

    /**
     * Count all rows (or rows matching a condition).
     *
     * @param  string|null $column
     * @param  mixed       $value
     * @return int
     */
    public static function count(?string $column = null, mixed $value = null): int {
        if ($column !== null) {
            return static::qb()->countWhere(static::tableName(), $column, $value);
        }
        $rows = static::qb()->raw("SELECT COUNT(*) AS `count` FROM `" . static::tableName() . "`");
        return (int) ($rows[0]->count ?? 0);
    }

    /**
     * Paginate using DB-level LIMIT / OFFSET.
     *
     * Two queries are run: COUNT(*) for the total, then SELECT with
     * LIMIT + OFFSET so only the requested page is fetched from the DB.
     *
     * Usage:
     *   $result = User::paginate(15);
     *   foreach ($result->items() as $user) { ... }
     *   echo $result->links();           // Previous / Next
     *   echo $result->numberedLinks();   // 1 2 3 … 10
     *
     * @param  int $perPage
     * @return LengthAwarePaginator
     */
    public static function paginate(int $perPage = 15): LengthAwarePaginator {
        $currentPage = Paginator::currentPage();

        $result = static::qb()->paginateTable(
            static::tableName(),
            $perPage,
            $currentPage
        );

        $items = array_map(
            fn($row) => static::fromRow((array) $row),
            $result['items']
        );

        return new LengthAwarePaginator($items, $result['total'], $perPage, $currentPage);
    }

    /**
     * Paginate rows matching a column condition.
     *
     * Usage:
     *   $result = Post::paginateWhere('status', 'published', 10);
     *
     * @param  string $column
     * @param  mixed  $value
     * @param  int    $perPage
     * @param  string $operator
     * @return LengthAwarePaginator
     */
    public static function paginateWhere(
        string $column,
        mixed $value,
        int $perPage = 15,
        string $operator = '='
    ): LengthAwarePaginator {
        $currentPage = Paginator::currentPage();

        $result = static::qb()->paginateTable(
            static::tableName(),
            $perPage,
            $currentPage,
            [$column, $operator, $value]
        );

        $items = array_map(
            fn($row) => static::fromRow((array) $row),
            $result['items']
        );

        return new LengthAwarePaginator($items, $result['total'], $perPage, $currentPage);
    }

    // Instance methods

    /**
     * Mass-assign attributes. Respects $fillable.
     *
     * @param  array $data
     * @return static
     */
    public function fill(array $data): static {
        foreach (static::filterFillable($data) as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Persist changes back to the database.
     * Updates only dirty (changed) attributes.
     *
     * @param  array $data  optional - shortcut for fill() + save()
     * @return bool
     */
    public function update(array $data = []): bool {
        if (!empty($data)) {
            $this->fill($data);
        }

        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true; // nothing to do
        }

        $dirty = static::withTimestamps($dirty, false);
        $pk    = static::$primaryKey;

        $affected = static::qb()->updateWhere(
            static::tableName(),
            $dirty,
            $pk,
            $this->attributes[$pk]
        );

        // Sync original so the model is no longer "dirty"
        $this->original = array_merge($this->original, $dirty);

        return $affected > 0;
    }

    /**
     * Delete this row from the database.
     *
     * @return bool
     */
    public function delete(): bool {
        $pk = static::$primaryKey;
        $affected = static::qb()->deleteWhere(
            static::tableName(),
            $pk,
            $this->attributes[$pk]
        );
        $this->exists = false;
        return $affected > 0;
    }

    /**
     * Insert (when new) or update (when loaded from DB).
     *
     * @return bool
     */
    public function save(): bool {
        if ($this->exists) {
            return $this->update();
        }

        $data = static::filterFillable($this->attributes);
        $data = static::withTimestamps($data, true);

        $id = static::qb()->insertRow(static::tableName(), $data);

        $this->attributes[static::$primaryKey] = $id;
        $this->original = $this->attributes;
        $this->exists   = true;

        return (bool) $id;
    }

    // Attribute access
    public function __get(string $name): mixed {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool {
        return isset($this->attributes[$name]);
    }

    /**
     * Return attributes as a plain array, excluding $hidden columns.
     *
     * @return array
     */
    public function toArray(): array {
        if (empty(static::$hidden)) {
            return $this->attributes;
        }
        return array_diff_key($this->attributes, array_flip(static::$hidden));
    }

    /**
     * JSON-serialisable representation (also hides $hidden columns).
     */
    public function toJson(): string {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Hydrate a model instance from a raw DB row.
     *
     * @param  array $row
     * @param  bool  $exists  Mark as a persisted row?
     * @return static
     */
    protected static function fromRow(array $row, bool $exists = true): static {
        // Use no-constructor instantiation so subclasses don't need a specific ctor.
        $instance = new static();
        $instance->attributes = $row;
        $instance->original   = $row;
        $instance->exists     = $exists;
        return $instance;
    }

    /**
     * Filter $data down to $fillable columns (if $fillable is defined).
     */
    private static function filterFillable(array $data): array {
        if (empty(static::$fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    /**
     * Append timestamp fields when $timestamps is enabled.
     *
     * @param  array $data
     * @param  bool  $isInsert  true -> set created_at; false -> only updated_at
     * @return array
     */
    private static function withTimestamps(array $data, bool $isInsert): array {
        if (!static::$timestamps) {
            return $data;
        }

        $now = date('Y-m-d H:i:s');

        if ($isInsert) {
            $data['created_at'] = $data['created_at'] ?? $now;
        }
        $data['updated_at'] = $now;

        return $data;
    }

    /**
     * Return only attributes that have changed since the model was loaded.
     */
    private function getDirty(): array {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }
}
