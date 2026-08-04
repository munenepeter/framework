<?php

namespace Tabel\Tests\Database;

use PHPUnit\Framework\TestCase;
use Tabel\Database\Model;
use Tabel\Database\QueryBuilder;

/**
 * ModelTest
 *
 * Tests the base Model class using a concrete stub (StubUser) backed by
 * an in-memory SQLite database. The `app()` helper is not needed here -
 * we wire the PDO directly into QueryBuilder's singleton, and override
 * the protected qb() resolver via a test-friendly subclass.
 */

class StubUser extends Model {
    protected static string $table      = 'users';
    protected static string $primaryKey = 'id';
    protected static bool   $timestamps = true;
    protected static array  $fillable   = ['name', 'email', 'role'];
    protected static array  $hidden     = ['password'];

    // Expose the QueryBuilder singleton directly so tests can inject a PDO
    // without needing the app() helper.
    protected static function qb(): QueryBuilder {
        return QueryBuilder::getInstance(ModelTest::$pdo);
    }
}


class ModelTest extends TestCase {

    /** Shared in-memory PDO exposed to StubUser::qb(). */
    public static \PDO $pdo;

    protected function setUp(): void {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Reset QueryBuilder singleton
        $ref  = new \ReflectionClass(QueryBuilder::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        self::$pdo->exec("
            CREATE TABLE users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL,
                email      TEXT NOT NULL UNIQUE,
                role       TEXT NOT NULL DEFAULT 'user',
                password   TEXT,
                created_at TEXT,
                updated_at TEXT
            )
        ");
    }

    protected function tearDown(): void {
        $ref  = new \ReflectionClass(QueryBuilder::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    // Helpers
    private function seedUsers(int $count = 3): void {
        for ($i = 1; $i <= $count; $i++) {
            self::$pdo->exec("
                INSERT INTO users (name, email, role, created_at, updated_at)
                VALUES ('User {$i}', 'user{$i}@example.com',
                        '" . ($i === 1 ? 'admin' : 'user') . "',
                        datetime('now'), datetime('now'))
            ");
        }
    }

    // all()
    public function test_all_returns_array_of_model_instances(): void {
        $this->seedUsers(3);
        $users = StubUser::all();

        $this->assertCount(3, $users);
        $this->assertContainsOnlyInstancesOf(StubUser::class, $users);
    }

    public function test_all_returns_empty_array_on_empty_table(): void {
        $this->assertSame([], StubUser::all());
    }

    // find()
    public function test_find_returns_model_with_correct_attributes(): void {
        $this->seedUsers(3);
        $user = StubUser::find(2);

        $this->assertInstanceOf(StubUser::class, $user);
        $this->assertEquals('user2@example.com', $user->email);
        $this->assertEquals('2', (string) $user->id);
    }

    public function test_find_returns_null_when_not_found(): void {
        $this->seedUsers(1);
        $this->assertNull(StubUser::find(999));
    }

    // findOrFail()
    public function test_findOrFail_throws_when_not_found(): void {
        $this->expectException(\RuntimeException::class);
        StubUser::findOrFail(999);
    }

    public function test_findOrFail_returns_model_when_found(): void {
        $this->seedUsers(1);
        $user = StubUser::findOrFail(1);
        $this->assertInstanceOf(StubUser::class, $user);
    }

    // where()
    public function test_where_returns_matching_models(): void {
        $this->seedUsers(3);
        $admins = StubUser::where('role', 'admin');

        $this->assertCount(1, $admins);
        $this->assertEquals('user1@example.com', $admins[0]->email);
    }

    public function test_where_returns_empty_array_when_no_match(): void {
        $this->seedUsers(2);
        $result = StubUser::where('role', 'superadmin');
        $this->assertSame([], $result);
    }

    public function test_where_supports_operators(): void {
        $this->seedUsers(3);
        // All users whose id > 1
        $users = StubUser::where('id', 1, '>');
        $this->assertCount(2, $users);
    }

    // firstWhere()
    public function test_firstWhere_returns_single_model(): void {
        $this->seedUsers(3);
        $user = StubUser::firstWhere('email', 'user2@example.com');

        $this->assertInstanceOf(StubUser::class, $user);
        $this->assertEquals('User 2', $user->name);
    }

    public function test_firstWhere_returns_null_when_no_match(): void {
        $this->seedUsers(1);
        $this->assertNull(StubUser::firstWhere('email', 'nobody@example.com'));
    }

    // create()
    public function test_create_inserts_and_returns_model(): void {
        $user = StubUser::create([
            'name'  => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertInstanceOf(StubUser::class, $user);
        $this->assertEquals('Alice', $user->name);
        $this->assertNotNull($user->id);
    }

    public function test_create_respects_fillable_filter(): void {
        // 'password' is not in $fillable - it should be silently dropped
        $user = StubUser::create([
            'name'     => 'Bob',
            'email'    => 'bob@example.com',
            'password' => 's3cr3t',       // not in $fillable
        ]);

        // Confirm it was stored without password
        $row = self::$pdo->query("SELECT password FROM users WHERE id = {$user->id}")
                         ->fetch(\PDO::FETCH_ASSOC);
        $this->assertNull($row['password']);
    }

    public function test_create_sets_timestamps(): void {
        $user = StubUser::create(['name' => 'Carol', 'email' => 'carol@example.com']);

        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    // update()
    public function test_update_modifies_correct_row(): void {
        $this->seedUsers(2);
        $user = StubUser::find(1);
        $result = $user->update(['name' => 'Updated Name']);

        $this->assertTrue($result);

        $fresh = StubUser::find(1);
        $this->assertEquals('Updated Name', $fresh->name);
    }

    public function test_update_returns_true_when_nothing_changed(): void {
        $this->seedUsers(1);
        $user = StubUser::find(1);
        // No data passed - nothing is dirty
        $this->assertTrue($user->update());
    }

    // delete()
    public function test_delete_removes_row(): void {
        $this->seedUsers(2);
        $user = StubUser::find(1);
        $result = $user->delete();

        $this->assertTrue($result);
        $this->assertNull(StubUser::find(1));
        $this->assertCount(1, StubUser::all());
    }

    // save()
    public function test_save_inserts_new_model(): void {
        $user        = new StubUser();
        $user->name  = 'Dave';
        $user->email = 'dave@example.com';
        $user->role  = 'user';

        $result = $user->save();

        $this->assertTrue($result);
        $this->assertNotNull($user->id);
        $this->assertCount(1, StubUser::all());
    }

    public function test_save_updates_existing_model(): void {
        $this->seedUsers(1);
        $user = StubUser::find(1);
        $user->name = 'Modified';
        $user->save();

        $fresh = StubUser::find(1);
        $this->assertEquals('Modified', $fresh->name);
    }

    // count()
    public function test_count_returns_total_rows(): void {
        $this->seedUsers(4);
        $this->assertEquals(4, StubUser::count());
    }

    public function test_count_with_condition(): void {
        $this->seedUsers(3);
        $this->assertEquals(2, StubUser::count('role', 'user'));
    }

    public function test_count_returns_zero_on_empty_table(): void {
        $this->assertEquals(0, StubUser::count());
    }

    // raw()
    public function test_raw_select_returns_array(): void {
        $this->seedUsers(2);
        $rows = StubUser::raw('SELECT id, email FROM users ORDER BY id');

        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);
        $this->assertIsObject($rows[0]);
        $this->assertEquals('user1@example.com', $rows[0]->email);
    }

    public function test_raw_update_returns_affected_count(): void {
        $this->seedUsers(3);
        $affected = StubUser::raw(
            "UPDATE users SET role = :role WHERE role = :old",
            [':role' => 'member', ':old' => 'user']
        );
        $this->assertEquals(2, $affected);
    }

    // toArray() / $hidden
    public function test_toArray_excludes_hidden_columns(): void {
        self::$pdo->exec("
            INSERT INTO users (name, email, role, password, created_at, updated_at)
            VALUES ('Eve', 'eve@example.com', 'user', 'secret', datetime('now'), datetime('now'))
        ");
        $user = StubUser::find(1);
        $arr  = $user->toArray();

        $this->assertArrayHasKey('email', $arr);
        $this->assertArrayNotHasKey('password', $arr);
    }

    // toJson()
    public function test_toJson_returns_valid_json(): void {
        StubUser::create(['name' => 'Frank', 'email' => 'frank@example.com']);
        $user = StubUser::find(1);
        $json = $user->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('Frank', $decoded['name']);
        $this->assertArrayNotHasKey('password', $decoded);
    }

    // Attribute magic
    public function test_magic_get_returns_attribute(): void {
        $this->seedUsers(1);
        $user = StubUser::find(1);
        $this->assertEquals('User 1', $user->name);
    }

    public function test_magic_set_changes_attribute(): void {
        $this->seedUsers(1);
        $user       = StubUser::find(1);
        $user->name = 'Changed';
        $this->assertEquals('Changed', $user->name);
    }

    public function test_magic_get_returns_null_for_missing_attribute(): void {
        $user = new StubUser();
        $this->assertNull($user->nonexistent);
    }
}
