<?php

namespace Tabel\Database;

/**
 * QueryBuilder
 *
 * Thin PDO wrapper used internally by the Model base class.
 * All user-supplied values go through prepared-statement bindings -
 * never interpolated directly into SQL.
 *
 * @package Tabel\Database
 */
class QueryBuilder {

    private static ?QueryBuilder $instance = null;
    private \PDO $pdo;

    private function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public static function getInstance(\PDO $pdo): static {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }
        return self::$instance;
    }

    // Core execution helpers

    /**
     * Execute a SELECT query and return rows as instances of $fetchClass,
     * or plain associative arrays when $fetchClass is null.
     *
     * @param  string      $sql
     * @param  array       $bindings
     * @param  string|null $fetchClass  Fully-qualified class name for FETCH_CLASS
     * @return array
     */
    public function select(string $sql, array $bindings = [], ?string $fetchClass = null): array {
        $stmt = $this->run($sql, $bindings);

        if ($fetchClass !== null && class_exists($fetchClass)) {
            // PDO::FETCH_PROPS_LATE used to ensure a class model constructor runs b4 db cols are assigned to object properties.
            $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $fetchClass);
        } else {
            $stmt->setFetchMode(\PDO::FETCH_OBJ);
        }

        return $stmt->fetchAll();
    }

    /**
     * Execute an INSERT / UPDATE / DELETE and return the affected row count.
     *
     * @param  string $sql
     * @param  array  $bindings
     * @return int  rows affected
     */
    public function statement(string $sql, array $bindings = []): int {
        $stmt = $this->run($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Execute an INSERT and return the last inserted id.
     *
     * @param  string $sql
     * @param  array  $bindings
     * @return string|false
     */
    public function insert(string $sql, array $bindings = []): string|false {
        $this->run($sql, $bindings);
        return $this->pdo->lastInsertId();
    }

    // Convenience methods (used by Model and legacy code)

    /**
     * Select all rows from a table, ordered by created_at DESC.
     *
     * @param  string      $table
     * @param  string|null $fetchClass
     * @return array
     */
    public function selectAll(string $table, ?string $fetchClass = null): array {
        $sql = "SELECT * FROM `{$table}` ORDER BY `created_at` DESC";
        return $this->select($sql, [], $fetchClass);
    }

    /**
     * Select all rows matching a single column condition.
     *
     * @param  string      $table
     * @param  string      $column
     * @param  mixed       $value
     * @param  string      $operator  =, <, >, <=, >=, !=, LIKE
     * @param  string|null $fetchClass
     * @return array
     */
    public function selectWhere(
        string $table,
        string $column,
        mixed $value,
        string $operator = '=',
        ?string $fetchClass = null
    ): array {
        $allowed = ['=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE'];
        if (!in_array(strtoupper($operator), $allowed, true)) {
            throw new \InvalidArgumentException("Unsupported operator: {$operator}");
        }

        $sql = "SELECT * FROM `{$table}` WHERE `{$column}` {$operator} :value ORDER BY `created_at` DESC";
        return $this->select($sql, [':value' => $value], $fetchClass);
    }

    /**
     * Select a single row by primary key.
     *
     * @param  string      $table
     * @param  mixed       $id
     * @param  string      $primaryKey
     * @param  string|null $fetchClass
     * @return object|array|null
     */
    public function selectById(
        string $table,
        mixed $id,
        string $primaryKey = 'id',
        ?string $fetchClass = null
    ): object|array|null {
        $sql = "SELECT * FROM `{$table}` WHERE `{$primaryKey}` = :id LIMIT 1";
        $rows = $this->select($sql, [':id' => $id], $fetchClass);
        return $rows[0] ?? null;
    }

    /**
     * Insert a row and return the new id.
     *
     * @param  string $table
     * @param  array  $data  associative [ column => value ]
     * @return string|false
     */
    public function insertRow(string $table, array $data): string|false {
        $columns  = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_map(fn($c) => ":{$c}", array_keys($data)));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";

        $bindings = [];
        foreach ($data as $col => $val) {
            $bindings[":{$col}"] = $val;
        }

        $this->logWrite('insert', $table);
        return $this->insert($sql, $bindings);
    }

    /**
     * Update rows matching a single column condition.
     *
     * @param  string $table
     * @param  array  $data       [ column => newValue ]
     * @param  string $whereCol
     * @param  mixed  $whereVal
     * @return int  rows affected
     */
    public function updateWhere(string $table, array $data, string $whereCol, mixed $whereVal): int {
        $setParts = implode(', ', array_map(fn($c) => "`{$c}` = :set_{$c}", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$setParts} WHERE `{$whereCol}` = :where_val";

        $bindings = [':where_val' => $whereVal];
        foreach ($data as $col => $val) {
            $bindings[":set_{$col}"] = $val;
        }

        $this->logWrite('update', $table);
        return $this->statement($sql, $bindings);
    }

    /**
     * Delete rows matching a single column condition.
     *
     * @param  string $table
     * @param  string $whereCol
     * @param  mixed  $whereVal
     * @return int  rows affected
     */
    public function deleteWhere(string $table, string $whereCol, mixed $whereVal): int {
        $sql = "DELETE FROM `{$table}` WHERE `{$whereCol}` = :where_val";

        $this->logWrite('delete', $table);
        return $this->statement($sql, [':where_val' => $whereVal]);
    }

    /**
     * Count rows matching a single column condition.
     *
     * @param  string $table
     * @param  string $column
     * @param  mixed  $value
     * @return int
     */
    public function countWhere(string $table, string $column, mixed $value): int {
        $sql  = "SELECT COUNT(*) AS `count` FROM `{$table}` WHERE `{$column}` = :value";
        $stmt = $this->run($sql, [':value' => $value]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int) ($row['count'] ?? 0);
    }

    /**
     * Run an arbitrary raw SQL query. SELECT returns an array of objects,
     * everything else returns the affected row count.
     *
     * @param  string $sql
     * @param  array  $bindings
     * @return array|int
     */
    public function raw(string $sql, array $bindings = []): array|int {
        $stmt = $this->run($sql, $bindings);

        $verb = strtoupper(strtok(ltrim($sql), " \t\n\r"));
        if ($verb === 'SELECT') {
            $stmt->setFetchMode(\PDO::FETCH_OBJ);
            return $stmt->fetchAll();
        }

        return $stmt->rowCount();
    }

    /**
     * Paginate a table with a DB-level LIMIT / OFFSET.
     *
     * Runs two queries:
     *  1. COUNT(*) to get the total row count.
     *  2. SELECT with LIMIT + OFFSET to fetch only the current page.
     *
     * @param  string      $table
     * @param  int         $perPage
     * @param  int         $currentPage  1-indexed
     * @param  array       $where        optional [ column, operator, value ] triple
     * @param  string|null $fetchClass
     * @return array{total: int, items: array}
     */
    public function paginateTable(
        string $table,
        int $perPage,
        int $currentPage,
        array $where = [],
        ?string $fetchClass = null
    ): array {
        $bindings  = [];
        $whereSql  = '';

        if (!empty($where)) {
            [$col, $op, $val] = $where;
            $allowed = ['=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE'];
            if (!in_array(strtoupper($op), $allowed, true)) {
                throw new \InvalidArgumentException("Unsupported operator: {$op}");
            }
            $whereSql  = "WHERE `{$col}` {$op} :where_val";
            $bindings[':where_val'] = $val;
        }

        // Query 1: total count (cheap index scan)
        $countSql = "SELECT COUNT(*) AS `count` FROM `{$table}` {$whereSql}";
        $countRow = $this->run($countSql, $bindings)->fetch(\PDO::FETCH_ASSOC);
        $total    = (int) ($countRow['count'] ?? 0);

        // Query 2: page slice
        $offset = max(0, ($currentPage - 1) * $perPage);
        $dataSql = "SELECT * FROM `{$table}` {$whereSql}
                    ORDER BY `created_at` DESC
                    LIMIT :limit OFFSET :offset";

        // Named + positional can conflict in some drivers; use a fresh bindings map
        $dataBindings = $bindings;
        $dataBindings[':limit']  = $perPage;
        $dataBindings[':offset'] = $offset;

        $items = $this->select($dataSql, $dataBindings, $fetchClass);

        return ['total' => $total, 'items' => $items];
    }

    /**
     * JOIN two tables and return plain associative rows.
     *
     * @param  string $table1
     * @param  string $table2
     * @param  string $fk     foreign key column on table1
     * @param  string $pk     primary key column on table2
     * @return array
     */
    public function join(string $table1, string $table2, string $fk, string $pk): array {
        $sql = "SELECT * FROM `{$table1}`
                INNER JOIN `{$table2}` ON `{$table1}`.`{$fk}` = `{$table2}`.`{$pk}`";
        $stmt = $this->run($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Prepare and execute a statement, handling errors uniformly.
     */
    private function run(string $sql, array $bindings = []): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                "Database error: " . $e->getMessage(),
                500,
                $e
            );
        }
    }

    private function logWrite(string $operation, string $table): void {
        $actor = function_exists('auth') && auth()
            ? '<b>' . ucfirst(auth()->username) . '</b>'
            : ($_SERVER['REMOTE_ADDR'] ?? 'CLI');

        $verb = match ($operation) {
            'insert' => 'Inserted a new record into',
            'update' => 'Updated a record in',
            'delete' => 'Deleted a record from',
            default  => ucfirst($operation) . ' on',
        };

        if (function_exists('logger')) {
            logger("Info", "{$actor} {$verb} `{$table}` table");
        }
    }
}
