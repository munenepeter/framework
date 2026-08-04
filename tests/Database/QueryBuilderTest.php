<?php

namespace Tabel\Tests\Database;

use PHPUnit\Framework\TestCase;
use Tabel\Database\QueryBuilder;

/**
 * QueryBuilderTest
 *
 * Uses an in-memory SQLite database so every test is isolated and fast.
 */
class QueryBuilderTest extends TestCase {

    private \PDO $pdo;
    private QueryBuilder $qb;

    protected function setUp(): void {
        // Fresh in-memory SQLite for every test
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // reset the singleton so each test gets a clean QueryBuilder
        $reflection = new \ReflectionClass(QueryBuilder::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->qb = QueryBuilder::getInstance($this->pdo);

        $this->pdo->exec("
            CREATE TABLE users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT    NOT NULL,
                email      TEXT    NOT NULL UNIQUE,
                role       TEXT    NOT NULL DEFAULT 'user',
                created_at TEXT,
                updated_at TEXT
            )
        ");
    }

    protected function tearDown(): void {
        // Reset singleton between tests
        $reflection = new \ReflectionClass(QueryBuilder::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    // Helpers
    private function seedUsers(int $count = 3): void {
        for ($i = 1; $i <= $count; $i++) {
            $this->qb->insertRow('users', [
                'name'       => "User {$i}",
                'email'      => "user{$i}@example.com",
                'role'       => $i === 1 ? 'admin' : 'user',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // getInstance
    public function test_getInstance_returns_same_instance(): void {
        $a = QueryBuilder::getInstance($this->pdo);
        $b = QueryBuilder::getInstance($this->pdo);
        $this->assertSame($a, $b);
    }

    // insertRow
    public function test_insertRow_returns_new_id(): void {
        $id = $this->qb->insertRow('users', [
            'name'       => 'Alice',
            'email'      => 'alice@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertIsString($id);
        $this->assertEquals('1', $id);
    }

    public function test_insertRow_increments_id(): void {
        $this->seedUsers(2);

        $id = $this->qb->insertRow('users', [
            'name'       => 'Charlie',
            'email'      => 'charlie@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertEquals('3', $id);
    }

    // selectAll
    public function test_selectAll_returns_all_rows(): void {
        $this->seedUsers(3);
        $rows = $this->qb->selectAll('users');
        $this->assertCount(3, $rows);
    }

    public function test_selectAll_returns_empty_array_for_empty_table(): void {
        $rows = $this->qb->selectAll('users');
        $this->assertSame([], $rows);
    }

    // selectById
    public function test_selectById_returns_correct_row(): void {
        $this->seedUsers(3);
        $row = $this->qb->selectById('users', 2);
        $this->assertIsObject($row);
        $this->assertEquals('user2@example.com', $row->email);
    }

    public function test_selectById_returns_null_when_not_found(): void {
        $this->seedUsers(1);
        $row = $this->qb->selectById('users', 999);
        $this->assertNull($row);
    }

    // selectWhere
    public function test_selectWhere_filters_by_equality(): void {
        $this->seedUsers(3);
        $rows = $this->qb->selectWhere('users', 'role', 'admin');
        $this->assertCount(1, $rows);
        $this->assertEquals('user1@example.com', $rows[0]->email);
    }

    public function test_selectWhere_returns_empty_when_no_match(): void {
        $this->seedUsers(3);
        $rows = $this->qb->selectWhere('users', 'role', 'superadmin');
        $this->assertSame([], $rows);
    }

    public function test_selectWhere_rejects_invalid_operator(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->qb->selectWhere('users', 'name', 'Alice', 'DROP');
    }

    // updateWhere
    public function test_updateWhere_changes_correct_row(): void {
        $this->seedUsers(2);
        $affected = $this->qb->updateWhere('users', ['name' => 'Updated'], 'id', 1);

        $this->assertEquals(1, $affected);
        $row = $this->qb->selectById('users', 1);
        $this->assertEquals('Updated', $row->name);
    }

    public function test_updateWhere_returns_zero_when_no_match(): void {
        $this->seedUsers(1);
        $affected = $this->qb->updateWhere('users', ['name' => 'Ghost'], 'id', 999);
        $this->assertEquals(0, $affected);
    }

    // deleteWhere
    public function test_deleteWhere_removes_correct_row(): void {
        $this->seedUsers(3);
        $affected = $this->qb->deleteWhere('users', 'id', 2);

        $this->assertEquals(1, $affected);
        $this->assertCount(2, $this->qb->selectAll('users'));
        $this->assertNull($this->qb->selectById('users', 2));
    }

    public function test_deleteWhere_returns_zero_when_no_match(): void {
        $this->seedUsers(1);
        $affected = $this->qb->deleteWhere('users', 'id', 999);
        $this->assertEquals(0, $affected);
    }

    // countWhere
    public function test_countWhere_returns_correct_count(): void {
        $this->seedUsers(3);
        $count = $this->qb->countWhere('users', 'role', 'user');
        $this->assertEquals(2, $count);
    }

    public function test_countWhere_returns_zero_when_no_match(): void {
        $this->seedUsers(1);
        $count = $this->qb->countWhere('users', 'role', 'nobody');
        $this->assertEquals(0, $count);
    }

    // raw
    public function test_raw_select_returns_array_of_objects(): void {
        $this->seedUsers(2);
        $results = $this->qb->raw('SELECT * FROM users');
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertIsObject($results[0]);
    }

    public function test_raw_update_returns_affected_count(): void {
        $this->seedUsers(3);
        $affected = $this->qb->raw(
            "UPDATE users SET role = :role WHERE role = :old",
            [':role' => 'member', ':old' => 'user']
        );
        $this->assertEquals(2, $affected);
    }

    // select (core method)
    public function test_select_accepts_custom_sql_and_bindings(): void {
        $this->seedUsers(3);
        $rows = $this->qb->select(
            'SELECT * FROM users WHERE role = :role',
            [':role' => 'admin']
        );
        $this->assertCount(1, $rows);
        $this->assertEquals('admin', $rows[0]->role);
    }

    // statement (core method)
    public function test_statement_returns_affected_row_count(): void {
        $this->seedUsers(3);
        $count = $this->qb->statement(
            "DELETE FROM users WHERE role = :role",
            [':role' => 'user']
        );
        $this->assertEquals(2, $count);
    }
}
