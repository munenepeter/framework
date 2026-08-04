<?php

namespace Tabel\Tests\Database;

use PHPUnit\Framework\TestCase;
use Tabel\Database\QueryBuilder;
use Tabel\Modules\LengthAwarePaginator;
use Tabel\Modules\Paginator;

/**
 * PaginationTest
 *
 * Covers:
 *  - QueryBuilder::paginateTable()
 *  - Model::paginate()  /  Model::paginateWhere()
 *  - LengthAwarePaginator value object
 *  - Paginator::paginate() (in-memory array slicing)
 */

// Stub model (reuses the same pattern as ModelTest)

class StubPost extends \Tabel\Database\Model {
    protected static string $table      = 'posts';
    protected static string $primaryKey = 'id';
    protected static bool   $timestamps = true;
    protected static array  $fillable   = ['title', 'status'];

    protected static function qb(): QueryBuilder {
        return QueryBuilder::getInstance(PaginationTest::$pdo);
    }
}


class PaginationTest extends TestCase {

    public static \PDO $pdo;

    protected function setUp(): void {
        self::$pdo = new \PDO('sqlite::memory:');
        self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // reset QueryBuilder singleton
        $ref  = new \ReflectionClass(QueryBuilder::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true); //todo: fix this
        $prop->setValue(null, null);

        self::$pdo->exec("
            CREATE TABLE posts (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT NOT NULL,
                status     TEXT NOT NULL DEFAULT 'draft',
                created_at TEXT,
                updated_at TEXT
            )
        ");

        // Seed 25 rows: 15 published, 10 draft
        for ($i = 1; $i <= 25; $i++) {
            $status = $i <= 15 ? 'published' : 'draft';
            self::$pdo->exec("
                INSERT INTO posts (title, status, created_at, updated_at)
                VALUES ('Post {$i}', '{$status}', datetime('now'), datetime('now'))
            ");
        }

        // reset $_GET so tests don't bleed into each other
        $_GET = [];
    }

    protected function tearDown(): void {
        $ref  = new \ReflectionClass(QueryBuilder::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true); //todo: fix this
        $prop->setValue(null, null);

        $_GET = [];
    }

    // QueryBuilder::paginateTable
    public function test_paginateTable_returns_correct_total(): void {
        $qb     = QueryBuilder::getInstance(self::$pdo);
        $result = $qb->paginateTable('posts', 10, 1);

        $this->assertEquals(25, $result['total']);
    }

    public function test_paginateTable_returns_correct_page_size(): void {
        $qb    = QueryBuilder::getInstance(self::$pdo);
        $result = $qb->paginateTable('posts', 10, 1);

        $this->assertCount(10, $result['items']);
    }

    public function test_paginateTable_last_page_has_remainder_rows(): void {
        $qb     = QueryBuilder::getInstance(self::$pdo);
        $result = $qb->paginateTable('posts', 10, 3); // page 3 of 3 -> 5 rows

        $this->assertCount(5, $result['items']);
    }

    public function test_paginateTable_with_where_filters_total(): void {
        $qb     = QueryBuilder::getInstance(self::$pdo);
        $result = $qb->paginateTable('posts', 10, 1, ['status', '=', 'published']);

        $this->assertEquals(15, $result['total']);
        $this->assertCount(10, $result['items']);
    }

    public function test_paginateTable_with_where_second_page(): void {
        $qb     = QueryBuilder::getInstance(self::$pdo);
        $result = $qb->paginateTable('posts', 10, 2, ['status', '=', 'published']);

        $this->assertEquals(15, $result['total']);
        $this->assertCount(5, $result['items']);
    }

    public function test_paginateTable_rejects_invalid_operator(): void {
        $this->expectException(\InvalidArgumentException::class);
        $qb = QueryBuilder::getInstance(self::$pdo);
        $qb->paginateTable('posts', 10, 1, ['status', 'DROP', 'published']);
    }

    public function test_paginateTable_does_not_fetch_all_rows(): void {
        // even with 25 rows, page 1 with perPage 5 returns only 5
        $qb     = QueryBuilder::getInstance(self::$pdo);
        $result = $qb->paginateTable('posts', 5, 1);

        $this->assertCount(5, $result['items']);
        $this->assertEquals(25, $result['total']);
    }

    // LengthAwarePaginator value object
    public function test_paginator_computes_last_page(): void {
        $p = new LengthAwarePaginator([], 25, 10, 1);
        $this->assertEquals(3, $p->lastPage);
    }

    public function test_paginator_hasPrev_false_on_first_page(): void {
        $p = new LengthAwarePaginator([], 25, 10, 1);
        $this->assertFalse($p->hasPrev);
    }

    public function test_paginator_hasNext_true_on_first_page(): void {
        $p = new LengthAwarePaginator([], 25, 10, 1);
        $this->assertTrue($p->hasNext);
    }

    public function test_paginator_hasPrev_true_on_last_page(): void {
        $p = new LengthAwarePaginator([], 25, 10, 3);
        $this->assertTrue($p->hasPrev);
    }

    public function test_paginator_hasNext_false_on_last_page(): void {
        $p = new LengthAwarePaginator([], 25, 10, 3);
        $this->assertFalse($p->hasNext);
    }

    public function test_paginator_from_to_on_full_page(): void {
        $items = array_fill(0, 10, 'x');
        $p = new LengthAwarePaginator($items, 25, 10, 2);
        $this->assertEquals(11, $p->from);
        $this->assertEquals(20, $p->to);
    }

    public function test_paginator_from_to_on_partial_last_page(): void {
        $items = array_fill(0, 5, 'x');
        $p = new LengthAwarePaginator($items, 25, 10, 3);
        $this->assertEquals(21, $p->from);
        $this->assertEquals(25, $p->to);
    }

    public function test_paginator_from_to_zero_when_empty(): void {
        $p = new LengthAwarePaginator([], 0, 10, 1);
        $this->assertEquals(0, $p->from);
        $this->assertEquals(0, $p->to);
    }

    public function test_paginator_clamps_current_page_above_last(): void {
        $p = new LengthAwarePaginator([], 10, 10, 99);
        $this->assertEquals(1, $p->currentPage);
    }

    public function test_paginator_clamps_current_page_below_one(): void {
        $p = new LengthAwarePaginator([], 10, 10, -5);
        $this->assertEquals(1, $p->currentPage);
    }

    public function test_paginator_toArray_shape(): void {
        $items = [1, 2, 3];
        $p = new LengthAwarePaginator($items, 30, 10, 1);
        $arr = $p->toArray();

        $this->assertArrayHasKey('data', $arr);
        $this->assertArrayHasKey('total', $arr);
        $this->assertArrayHasKey('per_page', $arr);
        $this->assertArrayHasKey('current_page', $arr);
        $this->assertArrayHasKey('last_page', $arr);
        $this->assertArrayHasKey('from', $arr);
        $this->assertArrayHasKey('to', $arr);
        $this->assertSame($items, $arr['data']);
        $this->assertEquals(30, $arr['total']);
    }

    public function test_paginator_toJson_is_valid(): void {
        $p    = new LengthAwarePaginator([1, 2], 20, 10, 1);
        $json = $p->toJson();
        $this->assertJson($json);
        $this->assertEquals(20, json_decode($json)->total);
    }

    public function test_paginator_links_contains_next_not_prev_on_page_one(): void {
        $p    = new LengthAwarePaginator([], 25, 10, 1);
        $html = $p->links('/posts');
        $this->assertStringContainsString('page=2', $html);
        $this->assertStringNotContainsString('page=0', $html);
    }

    public function test_paginator_links_contains_prev_not_next_on_last_page(): void {
        $p    = new LengthAwarePaginator([], 25, 10, 3);
        $html = $p->links('/posts');
        $this->assertStringContainsString('page=2', $html);
        $this->assertStringNotContainsString('page=4', $html);
    }

    // Model::paginate
    public function test_model_paginate_returns_LengthAwarePaginator(): void {
        $_GET['page'] = '1';
        $result = StubPost::paginate(10);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_model_paginate_total_matches_table_count(): void {
        $_GET['page'] = '1';
        $result = StubPost::paginate(10);
        $this->assertEquals(25, $result->total);
    }

    public function test_model_paginate_items_are_model_instances(): void {
        $_GET['page'] = '1';
        $result = StubPost::paginate(10);
        $this->assertContainsOnlyInstancesOf(StubPost::class, $result->items());
    }

    public function test_model_paginate_page_2_has_correct_items(): void {
        $_GET['page'] = '2';
        $result = StubPost::paginate(10);
        $this->assertCount(10, $result->items());
        $this->assertEquals(2, $result->currentPage);
        $this->assertEquals(11, $result->from);
    }

    public function test_model_paginate_last_page_has_remainder(): void {
        $_GET['page'] = '3';
        $result = StubPost::paginate(10);
        $this->assertCount(5, $result->items());
        $this->assertEquals(3, $result->lastPage);
    }

    // Model::paginateWhere
    public function test_model_paginateWhere_filters_correctly(): void {
        $_GET['page'] = '1';
        $result = StubPost::paginateWhere('status', 'published', 10);

        $this->assertEquals(15, $result->total);
        $this->assertCount(10, $result->items());
        foreach ($result->items() as $post) {
            $this->assertEquals('published', $post->status);
        }
    }

    public function test_model_paginateWhere_second_page(): void {
        $_GET['page'] = '2';
        $result = StubPost::paginateWhere('status', 'published', 10);

        $this->assertCount(5, $result->items());
        $this->assertEquals(2, $result->lastPage);
    }

    // Paginator::paginate (in-memory)
    public function test_in_memory_paginate_slices_correctly(): void {
        $data = range(1, 50);
        $_GET['page'] = '2';

        $result = Paginator::paginate($data, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(50, $result->total);
        $this->assertEquals(2, $result->currentPage);
        $this->assertSame(range(11, 20), $result->items());
    }

    public function test_in_memory_paginate_defaults_to_page_1(): void {
        $data   = range(1, 30);
        $result = Paginator::paginate($data, 10);

        $this->assertEquals(1, $result->currentPage);
        $this->assertSame(range(1, 10), $result->items());
    }

    public function test_in_memory_paginate_last_page(): void {
        $data         = range(1, 25);
        $_GET['page'] = '3';

        $result = Paginator::paginate($data, 10);
        $this->assertSame(range(21, 25), $result->items());
    }

    public function test_in_memory_paginate_empty_data(): void {
        $result = Paginator::paginate([], 10);
        $this->assertEquals(0, $result->total);
        $this->assertSame([], $result->items());
        $this->assertEquals(0, $result->from);
    }

    public function test_in_memory_paginate_page_beyond_last_clamps(): void {
        $data         = range(1, 5);
        $_GET['page'] = '99';

        $result = Paginator::paginate($data, 10);
        // clamped to last page (1), so all 5 items returned
        $this->assertSame(range(1, 5), $result->items());
    }
}
